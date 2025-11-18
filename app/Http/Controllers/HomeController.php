<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Hashtag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index()
    {
        if (!Auth::check()) {
            return view('welcome');
        }

        $user = Auth::user();

        // Get personalized feed
        $posts = Post::forUser($user)
            ->with(['user', 'media', 'likes', 'hashtags'])
            ->latest()
            ->paginate(12);

        // Get suggestions for sidebar
        $suggestedUsers = User::where('id', '!=', $user->id)
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('following_id')
                    ->from('follows')
                    ->where('follower_id', $user->id);
            })
            ->inRandomOrder()
            ->take(5)
            ->get();

        // Get trending hashtags
        $trendingHashtags = Hashtag::trending()
            ->take(10)
            ->get();

        return view('home', compact('posts', 'suggestedUsers', 'trendingHashtags'));
    }

    /**
     * Show explore page with trending content.
     */
    public function explore()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Get trending posts
        $trendingPosts = Post::trending()
            ->with(['user', 'media', 'likes'])
            ->take(24)
            ->get();

        // Get popular hashtags
        $popularHashtags = Hashtag::trending()
            ->take(15)
            ->get();

        // Get suggested users
        $suggestedUsers = User::where('id', '!=', $user->id)
            ->inRandomOrder()
            ->take(8)
            ->get();

        return view('explore', compact('trendingPosts', 'popularHashtags', 'suggestedUsers'));
    }

    /**
     * Search for content (posts, users, hashtags).
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type', 'all'); // all, posts, users, hashtags

        if (!$query || strlen($query) < 2) {
            return back()->with('error', 'Please enter a search term with at least 2 characters.');
        }

        $results = [];

        switch ($type) {
            case 'posts':
                $results['posts'] = Post::where('caption', 'like', "%{$query}%")
                    ->with(['user', 'media'])
                    ->latest()
                    ->paginate(20);
                break;

            case 'users':
                $results['users'] = User::where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%");
                })
                ->withCount(['posts', 'followers'])
                ->paginate(20);
                break;

            case 'hashtags':
                $results['hashtags'] = Hashtag::where('name', 'like', "%{$query}%")
                    ->withCount('posts')
                    ->orderBy('usage_count', 'desc')
                    ->paginate(20);
                break;

            default: // all
                $results['posts'] = Post::where('caption', 'like', "%{$query}%")
                    ->with(['user', 'media'])
                    ->latest()
                    ->limit(10)
                    ->get();

                $results['users'] = User::where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%");
                })
                ->withCount(['posts', 'followers'])
                ->limit(5)
                ->get();

                $results['hashtags'] = Hashtag::where('name', 'like', "%{$query}%")
                    ->withCount('posts')
                    ->orderBy('usage_count', 'desc')
                    ->limit(5)
                    ->get();
                break;
        }

        return view('search', [
            'query' => $query,
            'type' => $type,
            'results' => $results
        ]);
    }

    /**
     * Get trending content for API requests.
     */
    public function trending(Request $request)
    {
        $type = $request->get('type', 'posts'); // posts, hashtags, users
        $limit = min($request->get('limit', 20), 50);

        switch ($type) {
            case 'posts':
                $data = Post::trending()
                    ->with(['user', 'media', 'likes'])
                    ->take($limit)
                    ->get();
                break;

            case 'hashtags':
                $data = Hashtag::trending()
                    ->take($limit)
                    ->get();
                break;

            case 'users':
                $data = User::withCount(['followers', 'posts'])
                    ->orderBy('followers_count', 'desc')
                    ->take($limit)
                    ->get();
                break;

            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'type' => $type
        ]);
    }
}