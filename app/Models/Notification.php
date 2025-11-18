<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable model (post, comment, etc.).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a like notification.
     */
    public static function createLikeNotification(User $user, Model $likeable): self
    {
        $likerName = $user->name;
        $type = match(get_class($likeable)) {
            Post::class => 'post_like',
            Comment::class => 'comment_like',
            default => 'like',
        };

        return self::create([
            'user_id' => $likeable->user_id,
            'type' => $type,
            'notifiable_type' => get_class($likeable),
            'notifiable_id' => $likeable->id,
            'data' => [
                'liker_id' => $user->id,
                'liker_name' => $likerName,
                'liker_avatar' => $user->avatar_url,
                'message' => "{$likerName} liked your {$type === 'post_like' ? 'post' : 'comment'}",
                'action_url' => route('posts.show', $likeable->post_id ?? $likeable->id),
                'icon' => '❤️',
                'color' => '#ef4444', // red-500
            ],
        ]);
    }

    /**
     * Create a comment notification.
     */
    public static function createCommentNotification(User $user, Post $post, Comment $comment): self
    {
        $commenterName = $user->name;
        $isReply = $comment->parent_id !== null;

        return self::create([
            'user_id' => $post->user_id,
            'type' => $isReply ? 'comment_reply' : 'post_comment',
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'data' => [
                'commenter_id' => $user->id,
                'commenter_name' => $commenterName,
                'commenter_avatar' => $user->avatar_url,
                'message' => "{$commenterName} {$isReply ? 'replied to' : 'commented on'} your post",
                'action_url' => route('posts.show', $post) . '#comments',
                'icon' => '💬',
                'color' => '#3b82f6', // blue-500
            ],
        ]);
    }

    /**
     * Create a follow notification.
     */
    public static function createFollowNotification(User $follower, User $following): self
    {
        $followerName = $follower->name;

        return self::create([
            'user_id' => $following->id,
            'type' => 'user_follow',
            'notifiable_type' => User::class,
            'notifiable_id' => $following->id,
            'data' => [
                'follower_id' => $follower->id,
                'follower_name' => $followerName,
                'follower_avatar' => $follower->avatar_url,
                'message' => "{$followerName} started following you",
                'action_url' => route('users.show', $follower->username ?? $follower->id),
                'icon' => '👤',
                'color' => '#8b5cf6', // violet-500
            ],
        ]);
    }

    /**
     * Create a message notification.
     */
    public static function createMessageNotification(User $sender, Message $message): self
    {
        return self::create([
            'user_id' => $message->receiver_id,
            'type' => 'new_message',
            'notifiable_type' => Message::class,
            'notifiable_id' => $message->id,
            'data' => [
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'sender_avatar' => $sender->avatar_url,
                'message' => $message->content,
                'action_url' => route('messages.show', $sender->username ?? $sender->id),
                'icon' => '✉️',
                'color' => '#10b981', // emerald-500
            ],
        ]);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Get unread notifications for a user.
     */
    public static function getUnreadNotifications(User $user)
    {
        return self::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    /**
     * Get all notifications for a user.
     */
    public static function getUserNotifications(User $user, int $limit = 20)
    {
        return self::where('user_id', $user->id)
            ->with(['user'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllAsRead(User $user): int
    {
        return self::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user.
     */
    public static function getUnreadCount(User $user): int
    {
        return self::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Delete old notifications (cleanup).
     */
    public static function cleanupOldNotifications(int $days = 30): int
    {
        return self::where('created_at', '<', now()->subDays($days))
            ->whereNotNull('read_at')
            ->delete();
    }
}