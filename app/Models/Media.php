<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'file_path',
        'file_type',
        'file_size',
        'order',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size' => 'integer',
            'order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the post that owns the media.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the URL attribute for the media file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the thumbnail URL attribute.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->file_type === 'image') {
            // For images, return the main URL (could implement image resizing later)
            return $this->url;
        }

        // For videos, return thumbnail path
        $thumbnailPath = str_replace('media/', 'thumbnails/', $this->file_path);
        $thumbnailPath = preg_replace('/\.[^.]+$/', '_thumb.jpg', $thumbnailPath);

        return Storage::exists($thumbnailPath) ? Storage::url($thumbnailPath) : $this->url;
    }

    /**
     * Get the metadata dimensions.
     */
    public function getWidth(): ?int
    {
        return $this->metadata['width'] ?? null;
    }

    /**
     * Get the metadata height.
     */
    public function getHeight(): ?int
    {
        return $this->metadata['height'] ?? null;
    }

    /**
     * Get the metadata duration for videos.
     */
    public function getDuration(): ?float
    {
        return $this->metadata['duration'] ?? null;
    }

    /**
     * Check if the media has been processed.
     */
    public function isProcessed(): bool
    {
        return ($this->metadata['processed'] ?? false) === true;
    }

    /**
     * Check if the thumbnail has been generated.
     */
    public function hasThumbnail(): bool
    {
        return ($this->metadata['thumbnail_generated'] ?? false) === true;
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate the media file.
     */
    public static function validateFile($file): array
    {
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'video/mp4',
            'video/mov',
        ];

        $maxImageSize = 10 * 1024 * 1024; // 10MB
        $maxVideoSize = 100 * 1024 * 1024; // 100MB

        $errors = [];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, GIF, MP4, and MOV files are allowed.';
        }

        $isImage = str_starts_with($file->getMimeType(), 'image/');
        $maxSize = $isImage ? $maxImageSize : $maxVideoSize;

        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            $errors[] = "File too large. Maximum size is {$maxSizeMB}MB for " . ($isImage ? 'images' : 'videos');
        }

        return $errors;
    }
}