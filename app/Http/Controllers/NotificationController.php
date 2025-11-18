<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = min($request->get('limit', 20), 50);

        $notifications = Notification::getUserNotifications($user, $limit);
        $unreadCount = Notification::getUnreadCount($user);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function count()
    {
        $user = Auth::user();
        $unreadCount = Notification::getUnreadCount($user);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();

        // Check if user owns this notification
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => [
                'notification' => $notification,
                'unread_count' => Notification::getUnreadCount($user),
            ],
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $markedCount = Notification::markAllAsRead($user);

        return response()->json([
            'success' => true,
            'data' => [
                'marked_count' => $markedCount,
                'unread_count' => 0,
            ],
            'message' => "Marked {$markedCount} notifications as read.",
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Notification $notification)
    {
        $user = Auth::user();

        // Check if user owns this notification
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
            'unread_count' => Notification::getUnreadCount($user),
        ]);
    }

    /**
     * Get real-time notification updates (for polling or SSE).
     */
    public function stream()
    {
        $user = Auth::user();

        // Set appropriate headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');

        $lastChecked = now();

        while (connection_aborted() === false) {
            // Check for new notifications
            $newNotifications = Notification::where('user_id', $user->id)
                ->where('created_at', '>', $lastChecked)
                ->whereNull('read_at')
                ->get();

            if ($newNotifications->isNotEmpty()) {
                foreach ($newNotifications as $notification) {
                    echo "data: " . json_encode([
                        'type' => 'notification',
                        'data' => $notification
                    ]) . "\n\n";
                    flush();
                }

                $lastChecked = now();
            }

            // Check for new likes (real-time popup)
            $recentLikes = $this->getRecentLikesForPopup($user);
            if ($recentLikes->isNotEmpty()) {
                foreach ($recentLikes as $likeData) {
                    echo "data: " . json_encode([
                        'type' => 'like_popup',
                        'data' => $likeData
                    ]) . "\n\n";
                    flush();
                }
            }

            // Wait before next check (5 seconds)
            sleep(5);
        }
    }

    /**
     * Get recent likes for popup animation.
     */
    private function getRecentLikesForPopup($user)
    {
        // Get likes on user's posts from the last 30 seconds
        $recentLikes = \App\Models\Post::where('user_id', $user->id)
            ->whereHas('likes', function ($query) {
                $query->where('created_at', '>=', now()->subSeconds(30))
                      ->with('user');
            })
            ->with(['likes' => function ($query) {
                $query->where('created_at', '>=', now()->subSeconds(30))
                      ->with('user')
                      ->latest();
            }])
            ->get()
            ->flatMap(function ($post) {
                return $post->likes->map(function ($like) use ($post) {
                    return [
                        'id' => $like->id,
                        'user' => $like->user,
                        'post_id' => $post->id,
                        'post_image' => $post->media->first()?->url ?? null,
                        'type' => 'like',
                        'created_at' => $like->created_at,
                        'message' => "{$like->user->name} liked your post",
                    ];
                });
            });

        return $recentLikes;
    }
}