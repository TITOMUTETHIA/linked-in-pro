<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    private PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Display the main feed.
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $page = $request->get('page', 1);

        $posts = $this->postService->getFeedForUser($user, $page);

        return view('home', compact('posts'));
    }

    /**
     * Show the post creation form.
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a new post with media and hashtags.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Post::class);

        $request->validate([
            'caption' => 'nullable|string|max:' . config('toongram.limits.max_caption_length'),
            'location' => 'nullable|string|max:255',
            'privacy_level' => 'required|in:public,friends,private',
            'media_files.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov|max:' . (config('toongram.limits.media.max_image_size') / 1024),
            'media_order' => 'nullable|array',
        ]);

        try {
            $post = $this->postService->createPost(
                $request->only(['caption', 'location', 'privacy_level']),
                $request->file('media_files') ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $post,
                'message' => 'Post created successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display a specific post.
     */
    public function show(Post $post)
    {
        $user = Auth::user();

        if (!$user || !$this->canUserViewPost($user, $post)) {
            abort(403, 'You cannot view this post.');
        }

        $post->load([
            'user',
            'media',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->with(['user', 'likes'])
                    ->latest();
            },
            'hashtags',
            'likes'
        ]);

        return view('posts.show', compact('post'));
    }

    /**
     * Show the post editing form.
     */
    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $post->load(['media', 'hashtags']);
        return view('posts.edit', compact('post'));
    }

    /**
     * Update a post.
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $request->validate([
            'caption' => 'nullable|string|max:' . config('toongram.limits.max_caption_length'),
            'location' => 'nullable|string|max:255',
            'privacy_level' => 'required|in:public,friends,private',
        ]);

        try {
            $updatedPost = $this->postService->updatePost(
                $post,
                $request->only(['caption', 'location', 'privacy_level'])
            );

            return response()->json([
                'success' => true,
                'data' => $updatedPost,
                'message' => 'Post updated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        try {
            $this->postService->deletePost($post);

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle like on a post.
     */
    public function toggleLike(Post $post): JsonResponse
    {
        try {
            $result = $this->postService->toggleLike($post);

            return response()->json([
                'success' => true,
                'liked' => $result['liked'],
                'like_count' => $result['like_count'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show explore page with trending content.
     */
    public function explore()
    {
        $trendingPosts = $this->postService->getTrendingPosts();

        $trendingHashtags = \App\Models\Hashtag::trending()
            ->take(15)
            ->get();

        $suggestedUsers = Follow::getSuggestedFollows(Auth::user(), 8);

        return view('explore', compact(
            'trendingPosts',
            'trendingHashtags',
            'suggestedUsers'
        ));
    }

    /**
     * Check if a user can view a post based on privacy settings.
     */
    private function canUserViewPost(User $user, Post $post): bool
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