<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityId extends Model
{
    use HasFactory;

    protected $table = 'entity_ids';

    protected $fillable = [
        'org_id',
        'entity_type',
        'entity_prefix',
        'sequence_number',
        'entity_internal_id',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'sequence_number' => 'integer',
        'entity_internal_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // The public_id is a computed column in the database
    protected $appends = ['public_id'];

    /**
     * Get the organization that owns this entity ID
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the public_id attribute (computed column accessor)
     * In case we need to access it programmatically
     */
    public function getPublicIdAttribute(): string
    {
        return $this->entity_prefix.'-'.str_pad($this->sequence_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope to filter by organization
     */
    public function scopeForOrganization($query, int $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to filter by entity type
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to find by public ID
     */
    public function scopeByPublicId($query, string $publicId, ?int $orgId = null)
    {
        // Parse the public ID (e.g., "LOC-0001" -> prefix="LOC", sequence=1)
        if (!preg_match('/^([A-Z]+)-(\d+)$/', $publicId, $matches)) {
            return $query->whereRaw('1 = 0'); // Invalid format, return empty
        }

        $prefix = $matches[1];
        $sequenceNumber = (int) $matches[2];

        $query = $query->where('entity_prefix', $prefix)
                      ->where('sequence_number', $sequenceNumber);

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query;
    }

    /**
     * Get the next sequence number for a given org and entity type
     */
    public static function getNextSequenceNumber(int $orgId, string $entityType): int
    {
        $lastSequence = static::where('org_id', $orgId)
            ->where('entity_type', $entityType)
            ->max('sequence_number');

        return ($lastSequence ?? 0) + 1;
    }

    /**
     * Find entity by public ID within organization
     */
    public static function findByPublicId(string $publicId, ?int $orgId = null): ?self
    {
        return static::byPublicId($publicId, $orgId)->first();
    }

    /**
     * Get the internal ID for a given public ID
     * For super admins, orgId can be null to allow accessing any organization's data
     */
    public static function resolveInternalId(string $publicId, ?int $orgId = null): ?int
    {
        $entityId = static::findByPublicId($publicId, $orgId);

        return $entityId?->entity_internal_id;
    }

    /**
     * Get the public ID for a given internal ID and entity type
     * For super admins, orgId can be null to allow accessing any organization's data
     */
    public static function getPublicId(int $internalId, string $entityType, ?int $orgId = null): ?string
    {
        $query = static::where('entity_type', $entityType)
            ->where('entity_internal_id', $internalId);
            
        if ($orgId !== null) {
            $query->where('org_id', $orgId);
        }
        
        $entityId = $query->first();

        return $entityId?->public_id;
    }
}
