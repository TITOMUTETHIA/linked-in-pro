<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload files for a post.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:102400',
        ]);

        $file = $request->file('file');
        $errors = Media::validateFile($file);

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }

        try {
            // Generate organized file path
            $datePath = now()->format('Y/m/d');
            $fileType = str_starts_with($file->getMimeType(), 'image/') ? 'images' : 'videos';
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = "media/{$datePath}/{$fileType}/{$filename}";

            // Store file
            Storage::disk('media')->put($filePath, file_get_contents($file));

            // Prepare metadata
            $metadata = [
                'original_filename' => $file->getClientOriginalName(),
                'processed' => true,
                'thumbnail_generated' => false,
            ];

            // Add file-specific metadata
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $imageInfo = getimagesize($file->getPathname());
                if ($imageInfo) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'file_path' => $filePath,
                    'file_type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video',
                    'file_size' => $file->getSize(),
                    'url' => Storage::disk('media')->url($filePath),
                    'metadata' => $metadata,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to upload file: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Serve thumbnail for media.
     */
    public function thumbnail(Media $media)
    {
        // Check if user can access this media
        if (!$this->canUserAccessMedia(Auth::user(), $media)) {
            abort(403);
        }

        $thumbnailPath = str_replace('media/', 'thumbnails/', $media->file_path);
        $thumbnailPath = preg_replace('/\.[^.]+$/', '_thumb.jpg', $thumbnailPath);

        if (!Storage::disk('thumbnails')->exists($thumbnailPath)) {
            // Generate thumbnail if it doesn't exist
            $this->generateThumbnail($media);
        }

        if (Storage::disk('thumbnails')->exists($thumbnailPath)) {
            $file = Storage::disk('thumbnails')->path($thumbnailPath);
            return response()->file($file);
        }

        // Fallback to original file
        if (Storage::disk('media')->exists($media->file_path)) {
            $file = Storage::disk('media')->path($media->file_path);
            return response()->file($file);
        }

        abort(404);
    }

    /**
     * Download original media.
     */
    public function download(Media $media)
    {
        // Check if user can access this media
        if (!$this->canUserAccessMedia(Auth::user(), $media)) {
            abort(403);
        }

        if (!Storage::disk('media')->exists($media->file_path)) {
            abort(404);
        }

        $file = Storage::disk('media')->path($media->file_path);
        $originalName = $media->metadata['original_filename'] ?? 'download';

        return response()->download($file, $originalName);
    }

    /**
     * Process media in background (thumbnail generation, etc.).
     */
    public function process(Media $media)
    {
        // Check if user can process this media
        if (!$this->canUserAccessMedia(Auth::user(), $media)) {
            abort(403);
        }

        if ($media->file_type === 'image') {
            $this->generateThumbnail($media);
        }

        return response()->json([
            'success' => true,
            'message' => 'Media processed successfully',
            'thumbnail_url' => $media->thumbnail_url,
        ]);
    }

    /**
     * Delete media.
     */
    public function destroy(Media $media)
    {
        $user = Auth::user();

        // Only the post owner can delete media
        if ($media->post->user_id !== $user->id) {
            abort(403);
        }

        try {
            // Delete original file
            Storage::disk('media')->delete($media->file_path);

            // Delete thumbnail
            $thumbnailPath = str_replace('media/', 'thumbnails/', $media->file_path);
            $thumbnailPath = preg_replace('/\.[^.]+$/', '_thumb.jpg', $thumbnailPath);
            Storage::disk('thumbnails')->delete($thumbnailPath);

            // Delete database record
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get media info.
     */
    public function show(Media $media)
    {
        if (!$this->canUserAccessMedia(Auth::user(), $media)) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $media->id,
                'file_type' => $media->file_type,
                'file_size' => $media->file_size,
                'formatted_size' => $media->getFormattedSize(),
                'url' => $media->url,
                'thumbnail_url' => $media->thumbnail_url,
                'width' => $media->getWidth(),
                'height' => $media->getHeight(),
                'duration' => $media->getDuration(),
                'metadata' => $media->metadata,
            ],
        ]);
    }

    /**
     * Check if a user can access media based on post privacy.
     */
    private function canUserAccessMedia($user, Media $media): bool
    {
        // If no user is authenticated, only allow public posts
        if (!$user) {
            return $media->post->privacy_level === 'public';
        }

        // User can access their own media
        if ($media->post->user_id === $user->id) {
            return true;
        }

        // Public posts can be accessed by anyone
        if ($media->post->privacy_level === 'public') {
            return true;
        }

        // Friends-only posts require following
        if ($media->post->privacy_level === 'friends') {
            return $user->isFollowing($media->post->user);
        }

        // Private posts can only be accessed by the author
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
            // Log error but don't fail the operation
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
        }
    }
}