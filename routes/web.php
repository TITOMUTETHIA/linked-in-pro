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

// Home page - social media feed
Route::get('/', [PostController::class, 'index'])->name('home');

// Explore/discovery page
Route::get('/explore', [PostController::class, 'explore'])->name('explore');

// Post routes
Route::get('/posts/create', [PostController::class, 'create'])->middleware('auth')->name('posts.create');
Route::post('/posts', [PostController::class, 'store'])->middleware('auth')->name('posts.store');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->middleware('auth')->name('posts.edit');
Route::put('/posts/{post}', [PostController::class, 'update'])->middleware('auth')->name('posts.update');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware('auth')->name('posts.destroy');
Route::post('/posts/{post}/like', [PostController::class, 'toggleLike'])->middleware('auth')->name('posts.like');

// Media routes
Route::post('/media/upload', [MediaController::class, 'upload'])->middleware('auth')->name('media.upload');
Route::get('/media/{media}/thumbnail', [MediaController::class, 'thumbnail'])->name('media.thumbnail');
Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');
Route::post('/media/{media}/process', [MediaController::class, 'process'])->middleware('auth')->name('media.process');
Route::delete('/media/{media}', [MediaController::class, 'destroy'])->middleware('auth')->name('media.destroy');
Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');

// Comment routes
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware('auth')->name('comments.store');
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');
Route::get('/comments/{comment}', [CommentController::class, 'show'])->name('comments.show');
Route::get('/comments/{comment}/replies', [CommentController::class, 'replies'])->name('comments.replies');
Route::put('/comments/{comment}', [CommentController::class, 'update'])->middleware('auth')->name('comments.update');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->middleware('auth')->name('comments.destroy');
Route::post('/comments/{comment}/like', [CommentController::class, 'toggleLike'])->middleware('auth')->name('comments.like');
Route::post('/comments/{comment}/report', [CommentController::class, 'report'])->middleware('auth')->name('comments.report');

// User profile routes
Route::get('/{username}', [UserController::class, 'show'])->name('users.show');
Route::post('/users/{user}/follow', [UserController::class, 'follow'])->middleware('auth')->name('users.follow');
Route::get('/users/{user}/followers', [UserController::class, 'followers'])->name('users.followers');
Route::get('/users/{user}/following', [UserController::class, 'following'])->name('users.following');

// Search routes
Route::get('/search/users', [UserController::class, 'search'])->middleware('auth')->name('users.search');

// Hashtag routes
Route::get('/hashtags/{hashtag}', [HashtagController::class, 'show'])->name('hashtags.show');
Route::get('/hashtags/trending', [HashtagController::class, 'trending'])->name('hashtags.trending');
Route::get('/hashtags/search', [HashtagController::class, 'search'])->name('hashtags.search');
Route::get('/hashtags/suggestions', [HashtagController::class, 'suggestions'])->name('hashtags.suggestions');
Route::get('/hashtags/{hashtag}/related', [HashtagController::class, 'related'])->name('hashtags.related');
Route::get('/hashtags/{hashtag}/stats', [HashtagController::class, 'stats'])->name('hashtags.stats');
Route::post('/hashtags', [HashtagController::class, 'store'])->middleware('auth')->name('hashtags.store');

// Message routes
Route::get('/messages', [UserController::class, 'messages'])->middleware('auth')->name('messages.index');
Route::get('/messages/{user}', [UserController::class, 'conversation'])->middleware('auth')->name('messages.show');
Route::post('/messages/{user}', [UserController::class, 'sendMessage'])->middleware('auth')->name('messages.send');
Route::get('/messages/unread', [UserController::class, 'unreadCount'])->middleware('auth')->name('messages.unread');

// Notification routes
Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth')->name('notifications.index');
Route::get('/notifications/count', [NotificationController::class, 'count'])->middleware('auth')->name('notifications.count');
Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('auth')->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('auth')->name('notifications.read-all');
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->middleware('auth')->name('notifications.destroy');
Route::get('/notifications/stream', [NotificationController::class, 'stream'])->middleware('auth')->name('notifications.stream');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
});

Route::delete('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

// Profile management
Route::get('/profile', [UserController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::put('/profile', [UserController::class, 'update'])->middleware('auth')->name('profile.update');
Route::post('/profile/avatar', [UserController::class, 'uploadAvatar'])->middleware('auth')->name('profile.avatar');
Route::delete('/profile', [UserController::class, 'destroy'])->middleware('auth')->name('profile.destroy');

// Legacy routes for backward compatibility (commented out but can be kept for now)
/*
Route::get('/jobs/create', [JobController::class, 'create'])->middleware('auth');
Route::post('/jobs', [JobController::class, 'store'])->middleware('auth');
Route::get('/tags/{tag:name}', TagController::class);
*/