<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HashtagController extends Controller
{
    /**
     * Display posts for a specific hashtag.
     */
    public function show(Hashtag $hashtag, Request $request)
    {
        $user = Auth::user();
        $sort = $request->get('sort', 'recent'); // recent, popular, trending

        $posts = $hashtag->posts()
            ->forUser($user)
            ->with(['user', 'media', 'likes', 'hashtags'])
            ->when($sort === 'popular', function ($query) {
                $query->withCount('likes')->orderBy('likes_count', 'desc');
            })
            ->when($sort === 'trending', function ($query) {
                $query->withCount(['likes' => function ($q) {
                    $q->where('created_at', '>=', now()->subDays(7));
                }])->orderBy('likes_count', 'desc');
            }, function ($query) {
                $query->latest();
            })
            ->paginate(20);

        $hashtag->loadCount('posts');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'hashtag' => $hashtag,
                    'posts' => $posts,
                ],
            ]);
        }

        return view('hashtags.show', compact('hashtag', 'posts', 'sort'));
    }

    /**
     * Display trending hashtags.
     */
    public function trending(Request $request)
    {
        $period = $request->get('period', 'week'); // day, week, month
        $limit = min($request->get('limit', 20), 50);

        $hashtags = Hashtag::trending()
            ->where('usage_count', '>', 0)
            ->take($limit)
            ->get()
            ->map(function ($hashtag) use ($period) {
                $hashtag->recent_posts_count = $this->getRecentPostsCount($hashtag, $period);
                return $hashtag;
            })
            ->sortByDesc('recent_posts_count')
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $hashtags,
            ]);
        }

        return view('hashtags.trending', compact('hashtags', 'period'));
    }

    /**
     * Search hashtags.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:50',
        ]);

        $query = strtolower($request->get('q'));
        $limit = min($request->get('limit', 20), 50);

        $hashtags = Hashtag::where('name', 'like', "%{$query}%")
            ->where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->take($limit)
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $hashtags,
                'query' => $query,
            ]);
        }

        return view('hashtags.search', compact('hashtags', 'query'));
    }

    /**
     * Get hashtag suggestions while typing.
     */
    public function suggestions(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:30',
        ]);

        $query = strtolower(str_replace('#', '', $request->get('q')));
        $limit = min($request->get('limit', 10), 20);

        $hashtags = Hashtag::where('name', 'like', "{$query}%")
            ->where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->take($limit)
            ->get(['name', 'usage_count']);

        return response()->json([
            'success' => true,
            'data' => $hashtags,
        ]);
    }

    /**
     * Get related hashtags (used with a hashtag).
     */
    public function related(Hashtag $hashtag, Request $request)
    {
        $limit = min($request->get('limit', 15), 30);

        // Find posts that use this hashtag
        $postsWithHashtag = $hashtag->posts()
            ->whereHas('hashtags')
            ->with('hashtags')
            ->take(100)
            ->get();

        // Collect other hashtags used in these posts
        $relatedHashtags = collect();
        foreach ($postsWithHashtag as $post) {
            foreach ($post->hashtags as $otherHashtag) {
                if ($otherHashtag->id !== $hashtag->id) {
                    $relatedHashtags->push($otherHashtag);
                }
            }
        }

        // Group by hashtag ID and count occurrences
        $relatedHashtags = $relatedHashtags
            ->groupBy('id')
            ->map(function ($group) {
                $hashtag = $group->first();
                $hashtag->co_occurrence_count = $group->count();
                return $hashtag;
            })
            ->sortByDesc('co_occurrence_count')
            ->take($limit)
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $relatedHashtags,
            ]);
        }

        return view('hashtags.related', compact('hashtag', 'relatedHashtags'));
    }

    /**
     * Get hashtag statistics.
     */
    public function stats(Hashtag $hashtag, Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $stats = [
            'total_posts' => $hashtag->getPostsCount(),
            'recent_posts' => $this->getRecentPostsCount($hashtag, $period),
            'usage_count' => $hashtag->usage_count,
            'top_posts' => $hashtag->getTopPosts(5),
            'recent_growth' => $this->getGrowthStats($hashtag, $period),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        }

        return view('hashtags.stats', compact('hashtag', 'stats', 'period'));
    }

    /**
     * Create a new hashtag (auto-generated from content).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:hashtags,name',
        ]);

        $name = strtolower(str_replace('#', '', trim($request->name)));
        $hashtag = Hashtag::create([
            'name' => $name,
            'usage_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'data' => $hashtag,
            'message' => 'Hashtag created successfully.',
        ]);
    }

    /**
     * Get recent posts count for a hashtag within a period.
     */
    private function getRecentPostsCount(Hashtag $hashtag, string $period): int
    {
        $dateRange = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };

        return $hashtag->posts()
            ->where('created_at', '>=', $dateRange)
            ->count();
    }

    /**
     * Get growth statistics for a hashtag.
     */
    private function getGrowthStats(Hashtag $hashtag, string $period): array
    {
        $periods = match ($period) {
            'day' => 24, // hours
            'week' => 7, // days
            'month' => 30, // days
            'year' => 12, // months
            default => 7,
        };

        $interval = match ($period) {
            'day' => 'hour',
            'week' => 'day',
            'month' => 'day',
            'year' => 'month',
            default => 'day',
        };

        // For now, return basic growth data
        // In a real implementation, you'd want to aggregate data by time periods
        $currentPeriod = $this->getRecentPostsCount($hashtag, $period);
        $previousPeriod = $this->getPreviousPeriodCount($hashtag, $period);

        return [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'growth_rate' => $previousPeriod > 0 ? (($currentPeriod - $previousPeriod) / $previousPeriod) * 100 : 0,
            'is_growing' => $currentPeriod > $previousPeriod,
        ];
    }

    /**
     * Get posts count for previous period.
     */
    private function getPreviousPeriodCount(Hashtag $hashtag, string $period): int
    {
        $dateRanges = match ($period) {
            'day' => [now()->subDays(2), now()->subDay()],
            'week' => [now()->subWeeks(2), now()->subWeek()],
            'month' => [now()->subMonths(2), now()->subMonth()],
            'year' => [now()->subYears(2), now()->subYear()],
            default => [now()->subWeeks(2), now()->subWeek()],
        };

        return $hashtag->posts()
            ->whereBetween('created_at', $dateRanges)
            ->count();
    }
}