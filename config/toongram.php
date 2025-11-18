<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Toongram Application Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the Toongram social media
    | platform including branding, limits, and feature settings.
    |
    */

    'app' => [
        'name' => 'Toongram',
        'tagline' => 'Share Your World',
        'description' => 'A next-generation social platform for sharing photos and videos with friends and communities.',
        'version' => '1.0.0',
        'url' => env('APP_URL'),
    ],

    'branding' => [
        'primary_color' => '#ec4899', // pink-500
        'secondary_color' => '#8b5cf6', // violet-500
        'accent_color' => '#f97316', // orange-500
        'logo_emoji' => '📸',
        'favicon' => 'favicon.ico',
    ],

    'limits' => [
        'max_caption_length' => 2000,
        'max_bio_length' => 500,
        'max_username_length' => 30,
        'max_comment_length' => 1000,
        'max_message_length' => 1000,

        'media' => [
            'max_image_size' => 10 * 1024 * 1024, // 10MB in bytes
            'max_video_size' => 100 * 1024 * 1024, // 100MB in bytes
            'max_files_per_post' => 10,
            'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif'],
            'allowed_video_types' => ['mp4', 'mov', 'avi'],
        ],

        'interactions' => [
            'max_follows_per_day' => 100,
            'max_likes_per_hour' => 100,
            'max_comments_per_hour' => 50,
            'max_messages_per_hour' => 30,
        ],
    ],

    'features' => [
        'registration' => env('ENABLE_REGISTRATION', true),
        'private_profiles' => env('ENABLE_PRIVATE_PROFILES', true),
        'hashtags' => env('ENABLE_HASHTAGS', true),
        'messaging' => env('ENABLE_MESSAGING', true),
        'notifications' => env('ENABLE_NOTIFICATIONS', true),
        'content_moderation' => env('ENABLE_CONTENT_MODERATION', true),
        'analytics' => env('ENABLE_ANALYTICS', true),
    ],

    'storage' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'path' => env('MEDIA_PATH', 'media'),
        'thumbnail_path' => env('THUMBNAIL_PATH', 'thumbnails'),
    ],

    'privacy' => [
        'default_profile_visibility' => 'public', // public, private
        'default_post_visibility' => 'public', // public, friends, private
        'data_retention_days' => 365,
        'allow_data_export' => true,
        'allow_account_deletion' => true,
    ],

    'notifications' => [
        'real_time' => env('ENABLE_REAL_TIME_NOTIFICATIONS', true),
        'email_notifications' => env('ENABLE_EMAIL_NOTIFICATIONS', false),
        'batch_processing' => true,
        'cleanup_days' => 30,
    ],

    'content' => [
        'safe_search' => true,
        'auto_moderation' => true,
        'report_threshold' => 5, // Number of reports before auto-review
        'featured_posts_count' => 10,
        'trending_hashtags_count' => 15,
    ],

    'ui' => [
        'posts_per_page' => 12,
        'comments_per_page' => 20,
        'users_per_page' => 20,
        'theme' => env('THEME', 'light'), // light, dark, auto
        'animations' => env('ENABLE_ANIMATIONS', true),
        'sound_effects' => env('ENABLE_SOUND_EFFECTS', true),
    ],

    'api' => [
        'rate_limiting' => true,
        'requests_per_minute' => 60,
        'cache_ttl' => 300, // 5 minutes
        'webhook_secret' => env('WEBHOOK_SECRET'),
    ],

    'security' => [
        'two_factor_auth' => env('ENABLE_2FA', false),
        'session_timeout' => 24 * 60, // 24 hours in minutes
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // 15 minutes
        'require_email_verification' => env('REQUIRE_EMAIL_VERIFICATION', false),
    ],
];