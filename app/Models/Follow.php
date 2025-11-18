<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'follower_id',
        'following_id',
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
     * Get the user who is following.
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Get the user who is being followed.
     */
    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_id');
    }

    /**
     * Follow a user.
     */
    public static function follow(User $follower, User $following): ?Follow
    {
        // Prevent self-following
        if ($follower->id === $following->id) {
            return null;
        }

        // Check if already following
        if (self::isFollowing($follower, $following)) {
            return null;
        }

        return self::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);
    }

    /**
     * Unfollow a user.
     */
    public static function unfollow(User $follower, User $following): bool
    {
        return self::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->delete() > 0;
    }

    /**
     * Toggle follow relationship.
     */
    public static function toggleFollow(User $follower, User $following): bool
    {
        if (self::isFollowing($follower, $following)) {
            return self::unfollow($follower, $following);
        } else {
            self::follow($follower, $following);
            return true;
        }
    }

    /**
     * Check if a user is following another user.
     */
    public static function isFollowing(User $follower, User $following): bool
    {
        return self::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->exists();
    }

    /**
     * Get the followers count for a user.
     */
    public static function getFollowersCount(User $user): int
    {
        return self::where('following_id', $user->id)->count();
    }

    /**
     * Get the following count for a user.
     */
    public static function getFollowingCount(User $user): int
    {
        return self::where('follower_id', $user->id)->count();
    }

    /**
     * Get all followers for a user.
     */
    public static function getFollowers(User $user)
    {
        return User::whereIn('id', function ($query) use ($user) {
            $query->select('follower_id')
                ->from('follows')
                ->where('following_id', $user->id);
        })->get();
    }

    /**
     * Get all users that a user is following.
     */
    public static function getFollowing(User $user)
    {
        return User::whereIn('id', function ($query) use ($user) {
            $query->select('following_id')
                ->from('follows')
                ->where('follower_id', $user->id);
        })->get();
    }

    /**
     * Get recent followers for a user.
     */
    public static function getRecentFollowers(User $user, int $limit = 10)
    {
        return self::where('following_id', $user->id)
            ->with('follower')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('follower');
    }

    /**
     * Get users that both users follow (mutual connections).
     */
    public static function getMutualFollowing(User $user1, User $user2)
    {
        $user1Following = self::where('follower_id', $user1->id)->pluck('following_id');
        $user2Following = self::where('follower_id', $user2->id)->pluck('following_id');

        return User::whereIn('id', $user1Following->intersect($user2Following))->get();
    }

    /**
     * Get suggested users to follow (users followed by people you follow).
     */
    public static function getSuggestedFollows(User $user, int $limit = 10)
    {
        // Get users followed by people you follow
        $suggestedIds = self::whereIn('follower_id', function ($query) use ($user) {
                $query->select('following_id')
                    ->from('follows')
                    ->where('follower_id', $user->id);
            })
            ->whereNotIn('following_id', function ($query) use ($user) {
                $query->select('following_id')
                    ->from('follows')
                    ->where('follower_id', $user->id);
            })
            ->where('following_id', '!=', $user->id)
            ->pluck('following_id');

        return User::whereIn('id', $suggestedIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}