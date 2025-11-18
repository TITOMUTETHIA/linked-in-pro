<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
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
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user who sent the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who received the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if the message is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Get unread messages for a user.
     */
    public static function getUnreadMessages(User $user)
    {
        return self::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->with('sender')
            ->latest()
            ->get();
    }

    /**
     * Get conversation between two users.
     */
    public static function getConversation(User $user1, User $user2)
    {
        return self::where(function ($query) use ($user1, $user2) {
                $query->where('sender_id', $user1->id)
                      ->where('receiver_id', $user2->id);
            })
            ->orWhere(function ($query) use ($user1, $user2) {
                $query->where('sender_id', $user2->id)
                      ->where('receiver_id', $user1->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get all conversations for a user.
     */
    public static function getConversations(User $user)
    {
        $sentMessages = self::where('sender_id', $user->id)
            ->selectRaw('receiver_id as other_user_id, MAX(created_at) as last_message_at')
            ->groupBy('receiver_id');

        $receivedMessages = self::where('receiver_id', $user->id)
            ->selectRaw('sender_id as other_user_id, MAX(created_at) as last_message_at')
            ->groupBy('sender_id');

        return $sentMessages->union($receivedMessages)
            ->orderBy('last_message_at', 'desc')
            ->get();
    }
}