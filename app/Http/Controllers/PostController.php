<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $posts = Post::forUser($user)
            ->with(['user', 'media', 'likes', 'hashtags'])
            ->latest()
            ->paginate(20);

        return view('home', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'caption' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
            'privacy_level' => 'required|in:public,friends,private',
            'media_files.*' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:102400',
            'media_order' => 'nullable|array',
        ]);

        $user = Auth::user();

        // Create the post
        $post = Post::create([
            'user_id' => $user->id,
            'caption' => $request->caption,
            'location' => $request->location,
            'privacy_level' => $request->privacy_level,
        ]);

        // Handle media uploads
        if ($request->hasFile('media_files')) {
            $mediaOrder = $request->media_order ?? [];
            $uploadedFiles = [];

            foreach ($request->file('media_files') as $index => $file) {
                $errors = Media::validateFile($file);
                if (!empty($errors)) {
                    return back()->withErrors(['media_files' => $errors]);
                }

                // Generate organized file path
                $datePath = now()->format('Y/m/d');
                $fileType = str_starts_with($file->getMimeType(), 'image/') ? 'images' : 'videos';
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $filePath = "media/{$datePath}/{$fileType}/{$filename}";

                // Store file
                Storage::disk('media')->put($filePath, file_get_contents($file));

                // Determine order
                $order = array_search($index, $mediaOrder) !== false ? $mediaOrder[$index] : $index;

                // Create media record with metadata
                $metadata = [
                    'original_filename' => $file->getClientOriginalName(),
                    'processed' => true,
                    'thumbnail_generated' => false,
                ];

                // Add file-specific metadata
                if (str_starts_with($file->getMimeType(), 'image/')) {
                    $imageInfo = getimagesize($file->getPathname());
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                }

                $media = $post->media()->create([
                    'file_path' => $filePath,
                    'file_type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video',
                    'file_size' => $file->getSize(),
                    'order' => $order,
                    'metadata' => $metadata,
                ]);

                $uploadedFiles[] = $media;
            }

            // Generate thumbnails for images
            foreach ($uploadedFiles as $media) {
                if ($media->file_type === 'image') {
                    $this->generateThumbnail($media);
                }
            }
        }

        // Process hashtags
        if ($request->caption) {
            $hashtags = Hashtag::extractHashtags($request->caption);
            foreach ($hashtags as $hashtagName) {
                $hashtag = Hashtag::findOrCreateByName($hashtagName);
                $post->hashtags()->attach($hashtag->id);
                $hashtag->incrementUsage();
            }
        }

        return redirect()->route('home')
            ->with('success', 'Post created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $user = Auth::user();

        // Check if user can view this post
        if (!$user || !$this->canUserViewPost($user, $post)) {
            abort(403, 'You cannot view this post.');
        }

        $post->load(['user', 'media', 'comments' => function ($query) {
            $query->whereNull('parent_id')->with('user', 'likes')->latest();
        }, 'hashtags', 'likes']);

        return view('posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $post->load(['media', 'hashtags']);
        return view('posts.edit', compact('post'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $request->validate([
            'caption' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
            'privacy_level' => 'required|in:public,friends,private',
        ]);

        $post->update([
            'caption' => $request->caption,
            'location' => $request->location,
            'privacy_level' => $request->privacy_level,
        ]);

        // Update hashtags
        $post->hashtags()->detach();
        if ($request->caption) {
            $hashtags = Hashtag::extractHashtags($request->caption);
            foreach ($hashtags as $hashtagName) {
                $hashtag = Hashtag::findOrCreateByName($hashtagName);
                $post->hashtags()->attach($hashtag->id);
                $hashtag->incrementUsage();
            }
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        // Delete associated media files
        foreach ($post->media as $media) {
            Storage::disk('media')->delete($media->file_path);
            $thumbnailPath = str_replace('media/', 'thumbnails/', $media->file_path);
            Storage::disk('thumbnails')->delete($thumbnailPath);
        }

        // Update hashtag usage counts
        foreach ($post->hashtags as $hashtag) {
            $hashtag->decrementUsage();
        }

        $post->delete();

        return redirect()->route('home')
            ->with('success', 'Post deleted successfully!');
    }

    /**
     * Toggle like on a post.
     */
    public function toggleLike(Post $post)
    {
        $user = Auth::user();
        $isLiked = Like::toggleLike($user, $post);

        return response()->json([
            'liked' => $isLiked,
            'like_count' => $post->getLikeCount(),
        ]);
    }

    /**
     * Show explore/trending posts.
     */
    public function explore()
    {
        $trendingPosts = Post::trending()
            ->with(['user', 'media', 'likes'])
            ->take(20)
            ->get();

        $trendingHashtags = Hashtag::trending()
            ->take(15)
            ->get();

        $suggestedUsers = Follow::getSuggestedFollows(Auth::user(), 8);

        return view('explore', compact('trendingPosts', 'trendingHashtags', 'suggestedUsers'));
    }

    /**
     * Check if a user can view a post based on privacy settings.
     */
    private function canUserViewPost(User $user, Post $post): bool
    {
        // User can view their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Public posts can be viewed by anyone
        if ($post->privacy_level === 'public') {
            return true;
        }

        // Friends-only posts require following
        if ($post->privacy_level === 'friends') {
            return $user->isFollowing($post->user);
        }

        // Private posts can only be viewed by the author
        return false;
    }

    /**
     * Generate thumbnail for an image.
     */
    private function generateThumbnail(Media $media): void
    {
        try {
            $imagePath = Storage::disk('media')->path($media->file_path);
            $imageInfo = getimagesize($imagePath);

            if (!$imageInfo) {
                return;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $thumbnailSize = 300;

            // Calculate thumbnail dimensions maintaining aspect ratio
            if ($width > $height) {
                $newWidth = $thumbnailSize;
                $newHeight = intval($height * $thumbnailSize / $width);
            } else {
                $newHeight = $thumbnailSize;
                $newWidth = intval($width * $thumbnailSize / $height);
            }

            // Create thumbnail using GD
            $source = match ($imageInfo[2]) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
                IMAGETYPE_PNG => imagecreatefrompng($imagePath),
                IMAGETYPE_GIF => imagecreatefromgif($imagePath),
                default => null,
            };

            if ($source) {
                $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                // Generate thumbnail path
                $thumbnailPath = str_replace('media/', 'thumbnails/', $media->file_path);
                $thumbnailPath = preg_replace('/\.[^.]+$/', '_thumb.jpg', $thumbnailPath);

                // Ensure thumbnail directory exists
                $thumbnailDir = dirname(Storage::disk('thumbnails')->path($thumbnailPath));
                if (!is_dir($thumbnailDir)) {
                    mkdir($thumbnailDir, 0755, true);
                }

                // Save thumbnail
                imagejpeg($thumbnail, Storage::disk('thumbnails')->path($thumbnailPath), 85);

                // Update metadata
                $media->update(['metadata' => array_merge($media->metadata, [
                    'thumbnail_generated' => true,
                ])]);

                imagedestroy($source);
                imagedestroy($thumbnail);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the post creation
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
        }
    }
}