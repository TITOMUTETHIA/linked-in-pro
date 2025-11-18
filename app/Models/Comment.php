<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that owns the comment.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the parent comment.
     */
    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the replies to the comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Get the likes for the comment.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Scope a query to only include root comments (no parent).
     */
    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to get comments for a specific post.
     */
    public function scopeForPost($query, Post $post)
    {
        return $query->where('post_id', $post->id);
    }

    /**
     * Check if the comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the total count of replies.
     */
    public function getReplyCount(): int
    {
        return $this->replies()->count();
    }

    /**
     * Get all nested replies recursively.
     */
    public function getAllReplies()
    {
        $replies = $this->replies()->with('user', 'likes')->get();

        foreach ($replies as $reply) {
            $reply->nested_replies = $reply->getAllReplies();
        }

        return $replies;
    }

    /**
     * Get the content truncated to a specific length.
     */
    public function getExcerpt(int $length = 100): string
    {
        return strlen($this->content) > $length
            ? substr($this->content, 0, $length) . '...'
            : $this->content;
    }

    /**
     * Check if the comment can be edited by the given user.
     */
    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Check if the comment can be deleted by the given user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        // User can delete their own comment
        if ($this->user_id === $user->id) {
            return true;
        }

        // Post owner can delete comments on their posts
        if ($this->post->user_id === $user->id) {
            return true;
        }

        return false;
    }
}