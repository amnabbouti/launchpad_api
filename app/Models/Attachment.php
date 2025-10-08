<?php

declare(strict_types = 1);

namespace App\Models;

use App\Traits\HasUuidv7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model {
    use HasFactory;
    use HasUuidv7;
    use SoftDeletes;

    protected $casts = [
        'size'       => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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

    public function checkInOuts(): MorphToMany {
        return $this->morphedByMany(CheckInOut::class, 'attachmentable', 'attachmentables');
    }

    public function getFileTypeNameAttribute(): string {
        $mimeToName = [
            'image/jpeg'                                                              => 'JPEG Image',
            'image/png'                                                               => 'PNG Image',
            'image/gif'                                                               => 'GIF Image',
            'image/svg+xml'                                                           => 'SVG Image',
            'application/pdf'                                                         => 'PDF Document',
            'application/msword'                                                      => 'Word Document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document',
            'application/vnd.ms-excel'                                                => 'Excel Spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'Excel Spreadsheet',
            'text/plain'                                                              => 'Text Document',
            'text/csv'                                                                => 'CSV Document',
            'application/zip'                                                         => 'ZIP Archive',
            'video/mp4'                                                               => 'MP4 Video',
            'audio/mpeg'                                                              => 'MP3 Audio',
        ];

        return $mimeToName[$this->file_type] ?? $this->file_type;
    }

    public function getHumanSizeAttribute(): string {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; ++$i) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getUrlAttribute(): string {
        return Storage::url($this->file_path);
    }

    public function items(): MorphToMany {
        return $this->morphedByMany(Item::class, 'attachmentable', 'attachmentables');
    }

    public function maintenances(): MorphToMany {
        return $this->morphedByMany(Maintenance::class, 'attachmentable', 'attachmentables');
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }
}
