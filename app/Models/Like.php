<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type',
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
     * Get the user that owns the like.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent likeable model (post or comment).
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Toggle a like for a user on a likeable model.
     */
    public static function toggleLike(User $user, Model $likeable): bool
    {
        $existingLike = $likeable->likes()
            ->where('user_id', $user->id)
            ->where('likeable_type', get_class($likeable))
            ->first();

        if ($existingLike) {
            // Remove existing like
            $existingLike->delete();
            return false;
        } else {
            // Create new like
            $likeable->likes()->create([
                'user_id' => $user->id,
                'likeable_type' => get_class($likeable),
            ]);
            return true;
        }
    }

    /**
     * Check if a user has liked a likeable model.
     */
    public static function isLikedBy(User $user, Model $likeable): bool
    {
        return $likeable->likes()
            ->where('user_id', $user->id)
            ->where('likeable_type', get_class($likeable))
            ->exists();
    }

    /**
     * Get the like count for a likeable model.
     */
    public static function getLikeCount(Model $likeable): int
    {
        return $likeable->likes()
            ->where('likeable_type', get_class($likeable))
            ->count();
    }

    /**
     * Get all users who liked the likeable model.
     */
    public static function getLikers(Model $likeable)
    {
        return User::whereIn('id', function ($query) use ($likeable) {
            $query->select('user_id')
                ->from('likes')
                ->where('likeable_id', $likeable->id)
                ->where('likeable_type', get_class($likeable));
        })->get();
    }

    /**
     * Get recent likes for a likeable model.
     */
    public static function getRecentLikes(Model $likeable, int $limit = 10)
    {
        return $likeable->likes()
            ->where('likeable_type', get_class($likeable))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Delete likes for a likeable model.
     */
    public static function deleteAllForModel(Model $likeable): void
    {
        $likeable->likes()
            ->where('likeable_type', get_class($likeable))
            ->delete();
    }

    /**
     * Transfer likes from one model to another (useful for migrations).
     */
    public static function transferLikes(Model $from, Model $to): void
    {
        $likes = $from->likes()
            ->where('likeable_type', get_class($from))
            ->get();

        foreach ($likes as $like) {
            $to->likes()->create([
                'user_id' => $like->user_id,
                'likeable_type' => get_class($to),
                'created_at' => $like->created_at,
                'updated_at' => $like->updated_at,
            ]);
        }

        self::deleteAllForModel($from);
    }
}