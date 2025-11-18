<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| Toongram Social Media Platform Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// Core Application Routes
// ============================================================================

// Home - Main social media feed
Route::get('/', [PostController::class, 'index'])->name('home');

// Explore - Discovery and trending content
Route::get('/explore', [PostController::class, 'explore'])->name('explore');

// ============================================================================
// Authentication Routes
// ============================================================================

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

// ============================================================================
// Authenticated User Routes
// ============================================================================

Route::middleware('auth')->group(function () {

    // Profile Management
    Route::get('/profile', [UserController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [UserController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::delete('/profile', [UserController::class, 'destroy'])->name('profile.destroy');

    // Post Creation
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');

    // Post Management
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Post Interactions
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike'])->name('posts.like');

    // Comments
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/like', [CommentController::class, 'toggleLike'])->name('comments.like');
    Route::post('/comments/{comment}/report', [CommentController::class, 'report'])->name('comments.report');

    // Media Management
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::post('/media/{media}/process', [MediaController::class, 'process'])->name('media.process');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    // User Interactions
    Route::post('/users/{user}/follow', [UserController::class, 'follow'])->name('users.follow');

    // Messaging
    Route::get('/messages', [UserController::class, 'messages'])->name('messages.index');
    Route::get('/messages/{user}', [UserController::class, 'conversation'])->name('messages.show');
    Route::post('/messages/{user}', [UserController::class, 'sendMessage'])->name('messages.send');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/stream', [NotificationController::class, 'stream'])->name('notifications.stream');

    // Search
    Route::get('/search/users', [UserController::class, 'search'])->name('users.search');
});

// ============================================================================
// Public Routes (No Authentication Required)
// ============================================================================

// Post Viewing
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

// Comments (Viewing)
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');
Route::get('/comments/{comment}', [CommentController::class, 'show'])->name('comments.show');
Route::get('/comments/{comment}/replies', [CommentController::class, 'replies'])->name('comments.replies');

// User Profiles
Route::get('/{username}', [UserController::class, 'show'])->name('users.show');
Route::get('/users/{user}/followers', [UserController::class, 'followers'])->name('users.followers');
Route::get('/users/{user}/following', [UserController::class, 'following'])->name('users.following');

// Hashtags
Route::get('/hashtags/{hashtag}', [HashtagController::class, 'show'])->name('hashtags.show');
Route::get('/hashtags/trending', [HashtagController::class, 'trending'])->name('hashtags.trending');
Route::get('/hashtags/search', [HashtagController::class, 'search'])->name('hashtags.search');
Route::get('/hashtags/suggestions', [HashtagController::class, 'suggestions'])->name('hashtags.suggestions');
Route::get('/hashtags/{hashtag}/related', [HashtagController::class, 'related'])->name('hashtags.related');
Route::get('/hashtags/{hashtag}/stats', [HashtagController::class, 'stats'])->name('hashtags.stats');

// Media Viewing
Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::get('/media/{media}/thumbnail', [MediaController::class, 'thumbnail'])->name('media.thumbnail');
Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');

// Public API Endpoints
Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
Route::get('/messages/unread', [UserController::class, 'unreadCount'])->name('messages.unread');