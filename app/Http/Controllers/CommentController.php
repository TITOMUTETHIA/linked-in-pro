<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Like;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a newly created comment.
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $user = Auth::user();

        // Check if user can comment on this post
        if (!$this->canUserCommentOnPost($user, $post)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot comment on this post.',
            ], 403);
        }

        // Validate parent comment if provided
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if (!$parentComment || $parentComment->post_id !== $post->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent comment.',
                ], 422);
            }
        }

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        $comment->load(['user', 'likes']);

        return response()->json([
            'success' => true,
            'data' => [
                'comment' => $comment,
                'reply_count' => $comment->getReplyCount(),
                'is_reply' => $comment->isReply(),
            ],
        ]);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update([
            'content' => $request->content,
        ]);

        $comment->load(['user', 'likes']);

        return response()->json([
            'success' => true,
            'data' => [
                'comment' => $comment,
            ],
        ]);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
        ]);
    }

    /**
     * Get replies for a comment.
     */
    public function replies(Comment $comment)
    {
        $user = Auth::user();

        // Check if user can view the parent comment
        if (!$user || !$this->canUserViewComment($user, $comment)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot view this comment.',
            ], 403);
        }

        $replies = $comment->replies()
            ->with(['user', 'likes'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $replies,
        ]);
    }

    /**
     * Toggle like on a comment.
     */
    public function toggleLike(Comment $comment)
    {
        $user = Auth::user();

        // Check if user can view the comment
        if (!$this->canUserViewComment($user, $comment)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot like this comment.',
            ], 403);
        }

        $isLiked = Like::toggleLike($user, $comment);

        return response()->json([
            'success' => true,
            'data' => [
                'liked' => $isLiked,
                'like_count' => $comment->likes()->count(),
            ],
        ]);
    }

    /**
     * Get comment with nested replies.
     */
    public function show(Comment $comment)
    {
        $user = Auth::user();

        if (!$user || !$this->canUserViewComment($user, $comment)) {
            abort(403);
        }

        $comment->load([
            'user',
            'likes',
            'replies' => function ($query) {
                $query->with(['user', 'likes'])
                      ->latest();
            },
            'parentComment.user'
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
        ]);
    }

    /**
     * Get all comments for a post.
     */
    public function index(Request $request, Post $post)
    {
        $user = Auth::user();

        // Check if user can view the post
        if (!$user || !$this->canUserViewPost($user, $post)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot view comments on this post.',
            ], 403);
        }

        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(['user', 'likes', 'replies' => function ($query) {
                $query->with(['user', 'likes'])
                      ->latest()
                      ->take(5); // Limit to 5 recent replies
            }])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments,
            'comment_count' => $post->getCommentCount(),
        ]);
    }

    /**
     * Report an inappropriate comment.
     */
    public function report(Comment $comment, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        if (!$this->canUserViewComment($user, $comment)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot report this comment.',
            ], 403);
        }

        // Create a report record (you'd need a Report model)
        // Report::create([
        //     'user_id' => $user->id,
        //     'comment_id' => $comment->id,
        //     'reason' => $request->reason,
        //     'description' => $request->description,
        //     'status' => 'pending',
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment reported successfully. Our team will review it.',
        ]);
    }

    /**
     * Check if a user can comment on a post based on privacy settings.
     */
    private function canUserCommentOnPost($user, Post $post): bool
    {
        // User can comment on their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Public posts allow comments from anyone
        if ($post->privacy_level === 'public') {
            return true;
        }

        // Friends-only posts require following
        if ($post->privacy_level === 'friends') {
            return $user->isFollowing($post->user);
        }

        // Private posts don't allow comments from others
        return false;
    }

    /**
     * Check if a user can view a comment.
     */
    private function canUserViewComment($user, Comment $comment): bool
    {
        // Check if user can view the parent post
        return $this->canUserViewPost($user, $comment->post);
    }

    /**
     * Check if a user can view a post.
     */
    private function canUserViewPost($user, Post $post): bool
    {
        // User can view their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Public posts can be viewed by anyone
        if ($post->privacy_level === 'public') {
            return true;
        }

        // Friends-only posts require following
        if ($post->privacy_level === 'friends') {
            return $user->isFollowing($post->user);
        }

        // Private posts can only be viewed by the author
        return false;
    }
}