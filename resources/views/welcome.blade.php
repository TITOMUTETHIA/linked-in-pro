<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Share Your World - Toongram</title>
    <script src="{{ asset('js/app.js') }}" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-pink-50 to-purple-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-4xl w-full">
            <!-- Hero Section -->
            <div class="text-center mb-12">
                <div class="mb-6">
                    <span class="text-6xl">📸</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-4">
                    Toongram
                </h1>
                <p class="text-xl md:text-2xl text-gray-600 mb-8">
                    Share life's moments with the world
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}"
                       class="px-8 py-3 bg-pink-600 hover:bg-pink-700 text-white font-semibold rounded-full text-lg transition-colors">
                        Get Started
                    </a>
                    <a href="{{ route('login') }}"
                       class="px-8 py-3 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-semibold rounded-full text-lg transition-colors">
                        Sign In
                    </a>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <div class="text-center p-6 bg-white rounded-2xl shadow-sm">
                    <div class="text-3xl mb-4">📸</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Capture & Share</h3>
                    <p class="text-gray-600">Share your photos and videos instantly with friends and followers</p>
                </div>
                <div class="text-center p-6 bg-white rounded-2xl shadow-sm">
                    <div class="text-3xl mb-4">🎨</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Creative Tools</h3>
                    <p class="text-gray-600">Use filters, stickers, and captions to make your content stand out</p>
                </div>
                <div class="text-center p-6 bg-white rounded-2xl shadow-sm">
                    <div class="text-3xl mb-4">💬</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect & Engage</h3>
                    <p class="text-gray-600">Build meaningful connections through likes, comments, and messages</p>
                </div>
            </div>

            <!-- Community Section -->
            <div class="text-center p-8 bg-white rounded-2xl shadow-sm">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    Join Our Growing Community
                </h2>
                <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
                    Discover trending content, connect with creators, and share your unique perspective with millions of users worldwide.
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-lg mx-auto text-center">
                    <div class="p-4">
                        <div class="text-2xl font-bold text-pink-600">🌍</div>
                        <p class="text-sm text-gray-600">Global</p>
                    </div>
                    <div class="p-4">
                        <div class="text-2xl font-bold text-pink-600">✨</div>
                        <p class="text-sm text-gray-600">Authentic</p>
                    </div>
                    <div class="p-4">
                        <div class="text-2xl font-bold text-pink-600">🎯</div>
                        <p class="text-sm text-gray-600">Creative</p>
                    </div>
                    <div class="p-4">
                        <div class="text-2xl font-bold text-pink-600">❤️</div>
                        <p class="text-sm text-gray-600">Supportive</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12 text-gray-500">
                <p class="mb-2">© 2025 Toongram - Share your world</p>
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="#" class="hover:text-gray-700">About</a>
                    <a href="#" class="hover:text-gray-700">Privacy</a>
                    <a href="#" class="hover:text-gray-700">Terms</a>
                    <a href="#" class="hover:text-gray-700">Help</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>