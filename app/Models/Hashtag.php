<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'usage_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the posts for the hashtag.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Scope a query to get trending hashtags.
     */
    public function scopeTrending($query)
    {
        return $query->orderBy('usage_count', 'desc')
                    ->where('usage_count', '>', 0)
                    ->orderBy('updated_at', 'desc');
    }

    /**
     * Increment the usage count for the hashtag.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->touch(); // Update the updated_at timestamp
    }

    /**
     * Decrement the usage count for the hashtag.
     */
    public function decrementUsage(): void
    {
        $this->decrement('usage_count');
        $this->touch(); // Update the updated_at timestamp
    }

    /**
     * Find or create a hashtag by name.
     */
    public static function findOrCreateByName(string $name): self
    {
        return self::firstOrCreate([
            'name' => strtolower(str_replace('#', '', trim($name))),
        ], [
            'usage_count' => 0,
        ]);
    }

    /**
     * Extract hashtags from text content.
     */
    public static function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Get the formatted hashtag with # symbol.
     */
    public function getFormattedNameAttribute(): string
    {
        return '#' . $this->name;
    }

    /**
     * Get posts count for this hashtag.
     */
    public function getPostsCount(): int
    {
        return $this->posts()->count();
    }

    /**
     * Get recent posts for this hashtag.
     */
    public function getRecentPosts(int $limit = 20)
    {
        return $this->posts()
            ->with('user', 'media')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Get top posts for this hashtag (by likes).
     */
    public function getTopPosts(int $limit = 20)
    {
        return $this->posts()
            ->withCount('likes')
            ->with('user', 'media')
            ->orderBy('likes_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}