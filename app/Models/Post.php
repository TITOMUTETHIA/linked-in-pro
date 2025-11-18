<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'caption',
        'location',
        'privacy_level',
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
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the media files for the post.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('order');
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the likes for the post.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class)->where('likeable_type', Post::class);
    }

    /**
     * Get the hashtags for the post.
     */
    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class);
    }

    /**
     * Scope a query to only include public posts.
     */
    public function scopePublic($query)
    {
        return $query->where('privacy_level', 'public');
    }

    /**
     * Scope a query to get posts visible to a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('privacy_level', 'public')
              ->orWhere('user_id', $user->id)
              ->orWhereHas('user.followers', function ($subQ) use ($user) {
                  $subQ->where('follower_id', $user->id);
              });
        });
    }

    /**
     * Scope a query to get trending posts.
     */
    public function scopeTrending($query)
    {
        return $query->withCount(['likes' => function ($q) {
            $q->where('created_at', '>=', now()->subDays(7));
        }])->orderBy('likes_count', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Check if the post is liked by a specific user.
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the total count of likes.
     */
    public function getLikeCount(): int
    {
        return $this->likes()->count();
    }

    /**
     * Get the total count of comments.
     */
    public function getCommentCount(): int
    {
        return $this->comments()->count();
    }
}