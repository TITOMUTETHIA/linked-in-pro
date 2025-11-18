<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\Post;
use App\Models\Follow;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        // Handle additional social media fields
        if ($request->has('username')) {
            $request->validate([
                'username' => 'required|string|max:30|unique:users,username,' . $user->id,
                'bio' => 'nullable|string|max:500',
                'avatar_url' => 'nullable|url|max:255',
                'private_profile' => 'boolean',
            ]);

            $validated['username'] = $request->username;
            $validated['bio'] = $request->bio;
            $validated['avatar_url'] = $request->avatar_url;
            $validated['private_profile'] = $request->boolean('private_profile', false);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload avatar for user.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:5120', // 5MB max
        ]);

        $user = Auth::user();
        $file = $request->file('avatar');

        try {
            // Generate unique filename
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = 'avatars/' . $filename;

            // Store file
            Storage::disk('public')->put($filePath, file_get_contents($file));

            // Delete old avatar if exists
            if ($user->avatar_url && str_contains($user->avatar_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $user->avatar_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Update user avatar
            $user->update([
                'avatar_url' => Storage::disk('public')->url($filePath)
            ]);

            return response()->json([
                'success' => true,
                'avatar_url' => $user->avatar_url,
                'message' => 'Avatar uploaded successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a user's profile.
     */
    public function show(User $user, Request $request): View
    {
        $currentUser = Auth::user();
        $tab = $request->get('tab', 'posts'); // posts, saved, tagged

        // Check if current user can view this profile
        if (!$currentUser || !$currentUser->canView($user)) {
            abort(403, 'You cannot view this profile.');
        }

        // Determine if following
        $isFollowing = $currentUser ? $currentUser->isFollowing($user) : false;
        $isOwnProfile = $currentUser && $currentUser->id === $user->id;

        $posts = collect();
        $savedPosts = collect();

        if ($tab === 'posts') {
            $posts = $user->posts()
                ->forUser($currentUser)
                ->with(['media', 'likes', 'hashtags'])
                ->latest()
                ->paginate(18);
        }

        $stats = [
            'posts_count' => $user->posts()->forUser($currentUser)->count(),
            'followers_count' => Follow::getFollowersCount($user),
            'following_count' => Follow::getFollowingCount($user),
        ];

        return view('profile.show', compact(
            'user',
            'posts',
            'savedPosts',
            'tab',
            'isFollowing',
            'isOwnProfile',
            'stats'
        ));
    }

    /**
     * Follow or unfollow a user.
     */
    public function follow(User $user)
    {
        $currentUser = Auth::user();

        // Cannot follow yourself
        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot follow yourself.',
            ], 422);
        }

        // Handle private profile follow requests
        if ($user->private_profile) {
            // For now, just follow (in a real app, you'd implement follow requests)
            $isFollowing = Follow::toggleFollow($currentUser, $user);
            $status = $isFollowing ? 'requested' : 'unfollowed';
        } else {
            $isFollowing = Follow::toggleFollow($currentUser, $user);
            $status = $isFollowing ? 'followed' : 'unfollowed';
        }

        return response()->json([
            'success' => true,
            'is_following' => $isFollowing,
            'status' => $status,
            'followers_count' => Follow::getFollowersCount($user),
        ]);
    }

    /**
     * Show followers list.
     */
    public function followers(User $user): View
    {
        $currentUser = Auth::user();

        // Check if can view followers (based on profile privacy)
        if (!$user->private_profile || ($currentUser && $currentUser->isFollowing($user))) {
            $followers = Follow::getFollowers($user);
        } else {
            $followers = collect();
        }

        return view('profile.followers', compact('user', 'followers'));
    }

    /**
     * Show following list.
     */
    public function following(User $user): View
    {
        $currentUser = Auth::user();

        // Check if can view following (based on profile privacy)
        if (!$user->private_profile || ($currentUser && $currentUser->isFollowing($user))) {
            $following = Follow::getFollowing($user);
        } else {
            $following = collect();
        }

        return view('profile.following', compact('user', 'following'));
    }

    /**
     * Show user's messages/conversations.
     */
    public function messages(): View
    {
        $user = Auth::user();

        $conversations = Message::getConversations($user);

        $conversations = $conversations->map(function ($conversation) use ($user) {
            $otherUserId = $conversation->other_user_id;
            $otherUser = User::find($otherUserId);

            $lastMessage = Message::where(function ($query) use ($user, $otherUser) {
                $query->where('sender_id', $user->id)
                      ->where('receiver_id', $otherUser->id);
            })->orWhere(function ($query) use ($user, $otherUser) {
                $query->where('sender_id', $otherUser->id)
                      ->where('receiver_id', $user->id);
            })->latest()->first();

            $unreadCount = Message::where('sender_id', $otherUser->id)
                                 ->where('receiver_id', $user->id)
                                 ->whereNull('read_at')
                                 ->count();

            return [
                'user' => $otherUser,
                'last_message' => $lastMessage,
                'unread_count' => $unreadCount,
                'last_message_at' => $conversation->last_message_at,
            ];
        })->sortByDesc('last_message_at');

        return view('messages.index', compact('conversations'));
    }

    /**
     * Show conversation with a specific user.
     */
    public function conversation(User $user): View
    {
        $currentUser = Auth::user();

        if ($currentUser->id === $user->id) {
            abort(400, 'Cannot have conversation with yourself.');
        }

        $messages = Message::getConversation($currentUser, $user);

        // Mark messages as read
        Message::where('sender_id', $user->id)
               ->where('receiver_id', $currentUser->id)
               ->whereNull('read_at')
               ->update(['read_at' => now()]);

        return view('messages.show', compact('user', 'messages'));
    }

    /**
     * Send a message to a user.
     */
    public function sendMessage(Request $request, User $user)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $sender = Auth::user();

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'content' => $request->content,
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json([
            'success' => true,
            'data' => $message,
            'message' => 'Message sent successfully!'
        ]);
    }

    /**
     * Get unread messages count.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $unreadCount = Message::getUnreadMessages($user)->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Search for users.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:50',
        ]);

        $query = $request->get('q');
        $limit = min($request->get('limit', 20), 50);

        $users = User::where(function ($q) use ($query) {
            $q->where('username', 'like', "%{$query}%")
              ->orWhere('name', 'like', "%{$query}%");
        })
        ->where('id', '!=', Auth::id())
        ->limit($limit)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'query' => $query,
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Delete user's media files
        foreach ($user->posts as $post) {
            foreach ($post->media as $media) {
                Storage::disk('media')->delete($media->file_path);
                $thumbnailPath = str_replace('media/', 'thumbnails/', $media->file_path);
                Storage::disk('thumbnails')->delete($thumbnailPath);
            }
        }

        // Delete avatar if exists
        if ($user->avatar_url && str_contains($user->avatar_url, '/storage/')) {
            $avatarPath = str_replace('/storage/', '', $user->avatar_url);
            Storage::disk('public')->delete($avatarPath);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Account deleted successfully.');
    }
}