<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Media;
use App\Models\Hashtag;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    /**
     * Create a new post with media and hashtags.
     */
    public function createPost(array $data, array $mediaFiles = null): Post
    {
        $user = auth()->user();

        $post = Post::create([
            'user_id' => $user->id,
            'caption' => $data['caption'] ?? null,
            'location' => $data['location'] ?? null,
            'privacy_level' => $data['privacy_level'] ?? 'public',
        ]);

        // Handle media uploads
        if ($mediaFiles && !empty($mediaFiles)) {
            $this->attachMediaToPost($post, $mediaFiles, $data['media_order'] ?? []);
        }

        // Process hashtags
        if (!empty($data['caption'])) {
            $this->processHashtags($post, $data['caption']);
        }

        return $post->load(['media', 'hashtags']);
    }

    /**
     * Update an existing post.
     */
    public function updatePost(Post $post, array $data): Post
    {
        $this->authorize('update', $post);

        $post->update([
            'caption' => $data['caption'] ?? null,
            'location' => $data['location'] ?? null,
            'privacy_level' => $data['privacy_level'] ?? 'public',
        ]);

        // Update hashtags
        if (isset($data['caption'])) {
            $this->processHashtags($post, $data['caption']);
        }

        return $post->fresh(['media', 'hashtags']);
    }

    /**
     * Delete a post and all associated files.
     */
    public function deletePost(Post $post): bool
    {
        $this->authorize('delete', $post);

        // Delete media files
        foreach ($post->media as $media) {
            $this->deleteMediaFiles($media);
        }

        // Update hashtag usage counts
        foreach ($post->hashtags as $hashtag) {
            $hashtag->decrementUsage();
        }

        return $post->delete();
    }

    /**
     * Attach media files to a post.
     */
    private function attachMediaToPost(Post $post, array $files, array $order = []): Collection
    {
        $mediaItems = collect();

        foreach ($files as $index => $file) {
            $this->validateMediaFile($file);

            $media = $this->storeMediaFile($file, $order[$index] ?? $index);
            $mediaItems->push($media);

            // Generate thumbnail for images
            if ($media->file_type === 'image') {
                $this->generateThumbnail($media);
            }
        }

        return $mediaItems;
    }

    /**
     * Store an uploaded media file.
     */
    private function storeMediaFile(UploadedFile $file, int $order): Media
    {
        $datePath = now()->format('Y/m/d');
        $fileType = $this->getFileType($file);
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = "media/{$datePath}/{$fileType}/{$filename}";

        // Store file
        Storage::disk('media')->put($filePath, file_get_contents($file));

        // Create metadata
        $metadata = [
            'original_filename' => $file->getClientOriginalName(),
            'processed' => true,
            'thumbnail_generated' => false,
        ];

        if ($fileType === 'image') {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
            }
        }

        return $post->media()->create([
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'order' => $order,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Validate a media file.
     */
    private function validateMediaFile(UploadedFile $file): void
    {
        $config = config('toongram.limits.media');
        $allowedImageTypes = $config['allowed_image_types'];
        $allowedVideoTypes = $config['allowed_video_types'];

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        // Check file type
        if (in_array($extension, $allowedImageTypes)) {
            if ($file->getSize() > $config['max_image_size']) {
                throw new \InvalidArgumentException('Image file too large. Maximum size: ' . ($config['max_image_size'] / 1024 / 1024) . 'MB');
            }
        } elseif (in_array($extension, $allowedVideoTypes)) {
            if ($file->getSize() > $config['max_video_size']) {
                throw new \InvalidArgumentException('Video file too large. Maximum size: ' . ($config['max_video_size'] / 1024 / 1024) . 'MB');
            }
        } else {
            throw new \InvalidArgumentException('Invalid file type. Allowed: ' . implode(', ', array_merge($allowedImageTypes, $allowedVideoTypes)));
        }
    }

    /**
     * Get file type from uploaded file.
     */
    private function getFileType(UploadedFile $file): string
    {
        return str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video';
    }

    /**
     * Generate thumbnail for media.
     */
    private function generateThumbnail(Media $media): void
    {
        try {
            if ($media->file_type !== 'image') {
                return;
            }

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

    /**
     * Delete media files from storage.
     */
    private function deleteMediaFiles(Media $media): void
    {
        try {
            Storage::disk('media')->delete($media->file_path);

            $thumbnailPath = str_replace('media/', 'thumbnails/', $media->file_path);
            $thumbnailPath = preg_replace('/\.[^.]+$/', '_thumb.jpg', $thumbnailPath);
            Storage::disk('thumbnails')->delete($thumbnailPath);
        } catch (\Exception $e) {
            \Log::error('Failed to delete media files: ' . $e->getMessage());
        }
    }

    /**
     * Process hashtags in caption.
     */
    private function processHashtags(Post $post, string $caption): void
    {
        // Remove existing hashtags
        $post->hashtags()->detach();

        // Extract and create new hashtags
        $hashtags = Hashtag::extractHashtags($caption);
        foreach ($hashtags as $hashtagName) {
            $hashtag = Hashtag::findOrCreateByName($hashtagName);
            $post->hashtags()->attach($hashtag->id);
            $hashtag->incrementUsage();
        }
    }

    /**
     * Get posts for a user's feed.
     */
    public function getFeedForUser(User $user, int $page = 1, int $perPage = 12)
    {
        return Post::forUser($user)
            ->with(['user', 'media', 'likes', 'hashtags'])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get trending posts.
     */
    public function getTrendingPosts(int $limit = 24)
    {
        return Post::trending()
            ->with(['user', 'media', 'likes'])
            ->take($limit)
            ->get();
    }

    /**
     * Toggle like on a post and create notification.
     */
    public function toggleLike(Post $post): array
    {
        $user = auth()->user();
        $wasLiked = $post->isLikedBy($user);

        // Toggle the like
        if ($wasLiked) {
            $post->likes()->where('user_id', $user->id)->delete();
            $isLiked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $isLiked = true;
        }

        // Create notification for like if appropriate
        if ($isLiked && $post->user_id !== $user->id) {
            Notification::createLikeNotification($user, $post);
        }

        return [
            'liked' => $isLiked,
            'like_count' => $post->getLikeCount(),
        ];
    }
}