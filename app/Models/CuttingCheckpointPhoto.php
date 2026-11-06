<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CuttingCheckpointPhoto extends Model
{
    protected $fillable = ['cutting_checkpoint_result_id', 'path'];

    public function result(): BelongsTo
    {
        return $this->belongsTo(CuttingCheckpointResult::class, 'cutting_checkpoint_result_id');
    }

    public function exists(): bool
    {
        return $this->path !== null && Storage::disk('public')->exists($this->path);
    }

    /**
     * Inline data URI so the image is embedded directly in the PDF. Reading
     * the bytes and base64-encoding them avoids DomPDF's remote/local path
     * restrictions and guarantees no broken image references.
     */
    public function dataUri(): ?string
    {
        if (! $this->exists()) {
            return null;
        }

        $disk = Storage::disk('public');
        $mime = $disk->mimeType($this->path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($this->path));
    }
}
