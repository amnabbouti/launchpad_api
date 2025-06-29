<?php

namespace App\Traits;

use App\Services\EntityIdService;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasPublicId
{
    /**
     * auto-generate public IDs
     */
    protected static function bootHasPublicId(): void
    {
        static::created(function ($model) {
            static::generatePublicIdForModel($model);
        });

        static::deleted(function ($model) {
            $model->entityId()?->delete();
        });
    }
    
    /**
     * Generate public ID
     */
    protected static function generatePublicIdForModel($model): void
    {
        try {
            $entityType = static::getEntityType();
            $entityIdService = app(EntityIdService::class);

            // For Organization model - use its own ID as org_id
            if ($entityType === 'organization') {
                $orgId = $model->id;
            }
            // For global entities (plans, licenses) that don't have org_id - use 0
            elseif (!isset($model->org_id) || $model->org_id === null) {
                // Skip public ID generation for super admin users
                if ($entityType === 'user') {
                    \Log::info('Skipping public ID generation for super admin user', [
                        'user_id' => $model->id,
                        'email' => $model->email ?? 'unknown'
                    ]);
                    return;
                }
                // For other global entities like plans, use org_id = 0
                $orgId = 0;
            }
            // For organization-scoped entities
            else {
                $orgId = $model->org_id;
            }

            $entityIdService->generatePublicId($orgId, $entityType, $model->id);
        } catch (\Exception $e) {
            \Log::warning('Failed to generate public ID', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'org_id' => $orgId ?? $model->org_id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the entity type for this model.
     */
    protected static function getEntityType(): string
    {
        // can be overridden in each model
        $className = class_basename(static::class);
        return strtolower($className);
    }

    /**
     * Relationship to EntityId
     */
    public function entityId(): HasOne
    {
        return $this->hasOne(\App\Models\EntityId::class, 'entity_internal_id')
            ->where('entity_type', static::getEntityType());
    }

    /**
     * Get the public ID for this model
     */
    public function getPublicIdAttribute(): ?string
    {
        return $this->entityId?->public_id;
    }

    /**
     * Backfill public IDs for existing models that don't have them
     * for models created in DB directly as well
     */
    public static function backfillMissingPublicIds(): void
    {
        $models = static::whereDoesntHave('entityId')->get();

        foreach ($models as $model) {
            static::generatePublicIdForModel($model);
        }
    }

    /**
     * Find model by public ID within the current organization
     */
    public static function findByPublicId(string $publicId, ?int $orgId): ?static
    {
        $entityId = \App\Models\EntityId::findByPublicId($publicId, $orgId);

        if (!$entityId || $entityId->entity_type !== static::getEntityType()) {
            return null;
        }

        $query = static::where('id', $entityId->entity_internal_id);
        if ($orgId !== null) {
            $query->where('org_id', $orgId);
        }

        return $query->first();
    }

    /**
     * Scope to find by public ID
     */
    public function scopeByPublicId($query, string $publicId, ?int $orgId)
    {
        $entityId = \App\Models\EntityId::findByPublicId($publicId, $orgId);

        if (!$entityId || $entityId->entity_type !== static::getEntityType()) {
            return $query->whereRaw('1 = 0'); 
        }

        $query = $query->where('id', $entityId->entity_internal_id);
        if ($orgId !== null) {
            $query->where('org_id', $orgId);
        }

        return $query;
    }
}
