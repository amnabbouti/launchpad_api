<?php

namespace App\Models;

use App\Traits\HasOrganizationScope;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasPublicId; 
    use HasFactory;
    use HasOrganizationScope;
    use SoftDeletes;

    protected $fillable = [
        'org_id',
        'filename',
        'original_filename',
        'file_type',
        'extension',
        'size',
        'file_path',
        'description',
        'category',
        'user_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the entity type for public_id generation
     */
    protected static function getEntityType(): string
    {
        return 'attachment';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Polymorphic relationships
    public function items(): MorphToMany
    {
        return $this->morphedByMany(Item::class, 'attachmentable', 'attachmentables');
    }

    public function maintenances(): MorphToMany
    {
        return $this->morphedByMany(Maintenance::class, 'attachmentable', 'attachmentables');
    }

    public function checkInOuts(): MorphToMany
    {
        return $this->morphedByMany(CheckInOut::class, 'attachmentable', 'attachmentables');
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getFileTypeNameAttribute(): string
    {
        $mimeToName = [
            'image/jpeg' => 'JPEG Image',
            'image/png' => 'PNG Image',
            'image/gif' => 'GIF Image',
            'image/svg+xml' => 'SVG Image',
            'application/pdf' => 'PDF Document',
            'application/msword' => 'Word Document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document',
            'application/vnd.ms-excel' => 'Excel Spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheet',
            'text/plain' => 'Text Document',
            'text/csv' => 'CSV Document',
            'application/zip' => 'ZIP Archive',
            'video/mp4' => 'MP4 Video',
            'audio/mpeg' => 'MP3 Audio',
        ];

        return $mimeToName[$this->file_type] ?? $this->file_type;
    }

    // Scopes
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('org_id', $organizationId);
    }
}
