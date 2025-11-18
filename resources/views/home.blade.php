@extends('layouts.app')

@section('content')
<!-- Like Popup Animation Container -->
<div id="likePopupContainer" class="fixed inset-0 pointer-events-none z-50"></div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Feed -->
        <div class="lg:col-span-2">
            <!-- Create Post Button -->
            @auth
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <a href="{{ route('posts.create') }}" class="flex items-center space-x-3 text-gray-500 hover:text-gray-700 transition">
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-10 h-10 rounded-full">
                    <span class="text-gray-600">Start a post...</span>
                    <svg class="w-5 h-5 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </a>
            </div>
            @endauth

            <!-- Posts Feed -->
            @if($posts->count() > 0)
                <div class="space-y-6">
                    @foreach($posts as $post)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <!-- Post Header -->
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="w-10 h-10 rounded-full object-cover">
                                        <div>
                                            <a href="{{ route('users.show', $post->user->username ?? $post->user->id) }}" class="font-semibold text-gray-900 hover:underline">
                                                {{ $post->user->name }}
                                            </a>
                                            <p class="text-sm text-gray-500">@{{ $post->user->username ?? $post->user->id }}</p>
                                            @if($post->location)
                                                <p class="text-xs text-gray-500 flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ $post->location }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                                        <span>{{ $post->created_at->diffForHumans() }}</span>
                                        @if($post->privacy_level !== 'public')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($post->privacy_level) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Post Caption -->
                                @if($post->caption)
                                    <div class="mt-3 text-gray-900">
                                        <p>{!! $this->formatCaptionWithHashtags($post->caption) !!}</p>
                                    </div>
                                @endif

                                <!-- Hashtags -->
                                @if($post->hashtags->count() > 0)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($post->hashtags as $hashtag)
                                            <a href="{{ route('hashtags.show', $hashtag) }}" class="text-pink-600 hover:text-pink-700 text-sm font-medium">
                                                #{{ $hashtag->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Media Content -->
                            @if($post->media->count() > 0)
                                <div class="relative">
                                    @if($post->media->count() === 1)
                                        <!-- Single Media -->
                                        <div class="aspect-square bg-gray-100">
                                            @if($post->media->first()->file_type === 'image')
                                                <img src="{{ $post->media->first()->url }}" alt="Post image" class="w-full h-full object-cover">
                                            @else
                                                <video controls class="w-full h-full">
                                                    <source src="{{ $post->media->first()->url }}" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        </div>
                                    @else
                                        <!-- Multiple Media (Grid) -->
                                        <div class="grid grid-cols-2 gap-1 aspect-square">
                                            @foreach($post->media->take(4) as $media)
                                                <div class="relative bg-gray-100 {{ $loop->index === 1 ? 'row-span-2' : '' }}">
                                                    @if($media->file_type === 'image')
                                                        <img src="{{ $media->thumbnail_url }}" alt="Post image" class="w-full h-full object-cover">
                                                    @else
                                                        <div class="relative w-full h-full">
                                                            <img src="{{ $media->thumbnail_url }}" alt="Video thumbnail" class="w-full h-full object-cover">
                                                            <div class="absolute inset-0 flex items-center justify-center">
                                                                <div class="bg-black bg-opacity-50 rounded-full p-3">
                                                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M8 5v14l11-7z"/>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if($loop->index === 4 && $post->media->count() > 4)
                                                        <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                                            <span class="text-white text-2xl font-bold">+{{ $post->media->count() - 4 }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Post Actions -->
                            <div class="px-4 py-3 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <!-- Like Button -->
                                        <button
                                            onclick="toggleLike({{ $post->id }})"
                                            class="flex items-center space-x-2 text-gray-500 hover:text-pink-600 transition"
                                            x-data="{ liked: {{ $post->isLikedBy(Auth::user()) ? 'true' : 'false' }}, likeCount: {{ $post->getLikeCount() }} }"
                                            :class="{ 'text-pink-600': liked }"
                                        >
                                            <svg x-show="!liked" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <svg x-show="liked" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            <span x-text="likeCount"></span>
                                        </button>

                                        <!-- Comment Button -->
                                        <a href="{{ route('posts.show', $post) }}#comments" class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <span>{{ $post->getCommentCount() }}</span>
                                        </a>

                                        <!-- Share Button -->
                                        <button class="flex items-center space-x-2 text-gray-500 hover:text-green-600 transition">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Save Button -->
                                    <button class="text-gray-500 hover:text-yellow-600 transition">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                    <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No posts yet</h3>
                        <p class="text-gray-500 mb-4">Start following people to see their posts in your feed!</p>
                        <a href="{{ route('explore') }}" class="inline-flex items-center px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white text-sm font-medium rounded-full transition duration-150 ease-in-out">
                            Explore Posts
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- User Profile Card -->
            @auth
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="text-center">
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-16 h-16 rounded-full mx-auto mb-3">
                    <h3 class="font-semibold text-gray-900">{{ Auth::user()->name }}</h3>
                    <p class="text-sm text-gray-500">@{{ Auth::user()->username ?? Auth::user()->id }}</p>
                    <a href="{{ route('users.show', Auth::user()->username ?? Auth::user()->id) }}" class="mt-3 block text-pink-600 hover:text-pink-700 text-sm font-medium">
                        View Profile
                    </a>
                </div>
            </div>
            @endauth

            <!-- Trending Hashtags -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Trending</h3>
                @php
                    $trendingHashtags = App\Models\Hashtag::trending()->take(5)->get();
                @endphp
                @if($trendingHashtags->count() > 0)
                    <div class="space-y-2">
                        @foreach($trendingHashtags as $hashtag)
                            <a href="{{ route('hashtags.show', $hashtag) }}" class="flex items-center justify-between hover:bg-gray-50 p-2 rounded transition">
                                <span class="text-pink-600 hover:text-pink-700">#{{ $hashtag->name }}</span>
                                <span class="text-xs text-gray-500">{{ $hashtag->usage_count }} posts</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No trending hashtags yet</p>
                @endif
                <a href="{{ route('hashtags.trending') }}" class="mt-3 block text-pink-600 hover:text-pink-700 text-sm font-medium">
                    View all →
                </a>
            </div>

            <!-- Suggested Users -->
            @auth
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Suggested for You</h3>
                @php
                    $suggestedUsers = App\Models\Follow::getSuggestedFollows(Auth::user(), 3);
                @endphp
                @if($suggestedUsers->count() > 0)
                    <div class="space-y-3">
                        @foreach($suggestedUsers as $suggestedUser)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $suggestedUser->avatar_url }}" alt="{{ $suggestedUser->name }}" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">{{ $suggestedUser->name }}</p>
                                        <p class="text-gray-500 text-xs">@{{ $suggestedUser->username ?? $suggestedUser->id }}</p>
                                    </div>
                                </div>
                                <button
                                    onclick="followUser({{ $suggestedUser->id }})"
                                    class="text-pink-600 hover:text-pink-700 text-sm font-medium"
                                >
                                    Follow
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No suggestions available</p>
                @endif
            </div>
            @endauth
        </div>
    </div>
</div>

<script>
// Toggle like functionality
function toggleLike(postId) {
    fetch(`/posts/${postId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const button = event.currentTarget;
            const isLiked = button.getAttribute('x-data').includes('true');
            button.setAttribute('x-data', `{ liked: ${!isLiked}, likeCount: ${data.like_count} }`);
        }
    });
}

// Follow user functionality
function followUser(userId) {
    fetch(`/users/${userId}/follow`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
@endsection

@php
    // Helper method to format caption with hashtags
    function formatCaptionWithHashtags($caption) {
        $pattern = '/#(\w+)/';
        return preg_replace_callback($pattern, function($matches) {
            return '<a href="' . route('hashtags.show', ['hashtag' => $matches[1]]) . '" class="text-pink-600 hover:text-pink-700 font-medium">#' . $matches[1] . '</a>';
        }, $caption);
    }
@endphp