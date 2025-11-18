<nav x-data="{ open: false, searchOpen: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2 text-2xl font-bold text-pink-600 hover:text-pink-700 transition-colors">
                        <span class="text-3xl">📸</span>
                        <span class="hidden sm:inline">Toongram</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-pink-600 border-pink-500' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Home
                    </a>
                    <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore') ? 'text-pink-600 border-pink-500' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Explore
                    </a>
                    <a href="{{ route('messages.index') }}" class="{{ request()->routeIs('messages.*') ? 'text-pink-600 border-pink-500' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Messages
                    </a>
                    <a href="{{ route('hashtags.trending') }}" class="{{ request()->routeIs('hashtags.*') ? 'text-pink-600 border-pink-500' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Discover
                    </a>
                </div>
            </div>

            <!-- Search Bar (Desktop) -->
            @auth
            <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
                <div class="relative w-full">
                    <input
                        type="text"
                        placeholder="Search users, hashtags..."
                        class="w-full px-4 py-2 pl-10 pr-4 text-gray-700 bg-gray-100 border-0 rounded-full focus:outline-none focus:bg-white focus:ring-2 focus:ring-pink-500"
                        x-data="{ search: '' }"
                        @input.debounce.300ms="search = $event.target.value"
                        x-show="!searchOpen"
                    >
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            @endauth

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-4">
                @auth
                <!-- Create Post Button -->
                <a href="{{ route('posts.create') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white text-sm font-medium rounded-full transition duration-150 ease-in-out">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create
                </a>

                <!-- Messages Icon -->
                <a href="{{ route('messages.index') }}" class="relative p-2 text-gray-600 hover:text-gray-900 transition duration-150 ease-in-out">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500" x-data="unreadMessages" x-show="unreadMessages > 0"></span>
                </a>

                <!-- Notifications Dropdown -->
                <x-dropdown align="right" width="80" id="notificationDropdown">
                    <x-slot name="trigger">
                        <button class="relative p-2 text-gray-600 hover:text-gray-900 transition duration-150 ease-in-out">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span id="notificationBadge" class="absolute top-0 right-0 block h-5 w-5 rounded-full bg-red-500 text-white text-xs font-medium flex items-center justify-center hidden">
                                0
                            </span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                                <button onclick="markAllNotificationsAsRead()" class="text-xs text-pink-600 hover:text-pink-700">
                                    Mark all read
                                </button>
                            </div>
                        </div>
                        <div id="notificationList" class="max-h-80 overflow-y-auto">
                            <div class="px-4 py-3 text-center text-sm text-gray-500">
                                Loading notifications...
                            </div>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 text-center">
                            <a href="#" class="text-sm text-pink-600 hover:text-pink-700 font-medium">
                                View all notifications
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                            <img class="h-8 w-8 rounded-full object-cover" src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}">
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- User Info -->
                        <div class="px-4 py-3 border-b border-gray-200">
                            <div class="flex items-center">
                                <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</div>
                                    <div class="text-sm text-gray-500">@{{ Auth::user()->username ?? Auth::user()->id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Link -->
                        <x-dropdown-link :href="route('users.show', Auth::user()->username ?? Auth::user()->id)">
                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </x-dropdown-link>

                        <!-- Settings -->
                        <x-dropdown-link :href="route('profile.edit')">
                            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </x-dropdown-link>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 hover:text-red-900">
                                <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <!-- Login/Register Buttons (Guest) -->
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                    Login
                </a>
                <a href="{{ route('register') }}" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Sign Up
                </a>
                @endauth

                <!-- Mobile Menu Button -->
                <div class="flex items-center sm:hidden">
                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-pink-600 border-pink-500' : 'text-gray-600 border-transparent hover:text-gray-800 hover:border-gray-300' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Home
            </a>
            <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore') ? 'text-pink-600 border-pink-500' : 'text-gray-600 border-transparent hover:text-gray-800 hover:border-gray-300' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Explore
            </a>
            <a href="{{ route('messages.index') }}" class="{{ request()->routeIs('messages.*') ? 'text-pink-600 border-pink-500' : 'text-gray-600 border-transparent hover:text-gray-800 hover:border-gray-300' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Messages
            </a>
            <a href="{{ route('hashtags.trending') }}" class="{{ request()->routeIs('hashtags.*') ? 'text-pink-600 border-pink-500' : 'text-gray-600 border-transparent hover:text-gray-800 hover:border-gray-300' }} block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Discover
            </a>
            @else
            <a href="{{ route('login') }}" class="text-gray-600 border-transparent hover:text-gray-800 hover:border-gray-300 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Login
            </a>
            <a href="{{ route('register') }}" class="text-gray-600 border-transparent hover:text-gray-800 hover:border-gray-300 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Sign Up
            </a>
            @endauth
        </div>

        @auth
        <!-- Mobile User Section -->
        <div class="pt-4 pb-3 border-t border-gray-200">
            <div class="flex items-center px-4">
                <div class="flex-shrink-0">
                    <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}">
                </div>
                <div class="ml-3">
                    <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-sm font-medium text-gray-500">@{{ Auth::user()->username ?? Auth::user()->id }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <a href="{{ route('users.show', Auth::user()->username ?? Auth::user()->id) }}" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    Profile
                </a>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    Settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>
