<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService extends BaseService
{
    public function __construct(Attachment $attachment)
    {
        parent::__construct($attachment);
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters
        $this->validateParams($params);

        return [
            'category' => $this->toString($params['category'] ?? null),
            'file_type' => $this->toString($params['file_type'] ?? null),
            'description' => $this->toString($params['description'] ?? null),
            'filename' => $this->toString($params['filename'] ?? null),
            'user_id' => $this->toInt($params['user_id'] ?? null),
            'min_size' => $this->toInt($params['min_size'] ?? null),
            'max_size' => $this->toInt($params['max_size'] ?? null),
            'attachmentable_type' => $this->toString($params['attachmentable_type'] ?? null),
            'attachmentable_id' => $this->toInt($params['attachmentable_id'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get filtered attachments
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value))
            ->when($filters['file_type'] ?? null, fn ($q, $value) => $q->where('file_type', 'like', "%{$value}%"))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['min_size'] ?? null, fn ($q, $value) => $q->where('size', '>=', $value))
            ->when($filters['max_size'] ?? null, fn ($q, $value) => $q->where('size', '<=', $value))
            ->when($filters['description'] ?? null, fn ($q, $value) => $q->where('description', 'like', "%{$value}%"))
            ->when($filters['filename'] ?? null, function ($q, $value) {
                return $q->where(function ($subQuery) use ($value) {
                    $subQuery->where('filename', 'like', "%{$value}%")
                        ->orWhere('original_filename', 'like', "%{$value}%");
                });
            })
            ->when($filters['attachmentable_type'] ?? null && $filters['attachmentable_id'] ?? null, 
                function ($q) use ($filters) {
                    return $q->whereHas('attachmentable', function ($subQuery) use ($filters) {
                        $subQuery->where('attachmentable_type', $filters['attachmentable_type'])
                            ->where('attachmentable_id', $filters['attachmentable_id']);
                    });
                }
            )
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations))
            ->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Find attachment by ID
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        if (! empty($relations)) {
            $relations = array_intersect($relations, ['attachmentable', 'user']);
        }

        return parent::findById($id, $columns, $relations, $appends);
    }

    /**
     * Create a new attachment with file upload.
     */
    public function createAttachment(array $data): Model
    {
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $file = $data['file'];
            $path = $file->store('attachments/'.date('Y/m'), 'public');
            $data = array_merge($data, [
                'filename' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'file_path' => $path,
            ]);
            unset($data['file']);
        }

        $attachment = $this->create($data);

        // If ok link the attachment
        if (isset($data['attachmentable_type']) && isset($data['attachmentable_id'])) {
            $this->linkAttachmentToEntity($attachment, $data['attachmentable_type'], $data['attachmentable_id']);
        }

        return $attachment;
    }

    /**
     * Link an attachment to a specific entity 
     */
    public function linkAttachmentToEntity(Model $attachment, string $entityType, int $entityId): void
    {
        $supportedTypes = $this->getSupportedEntityTypes();
        
        if (!in_array($entityType, $supportedTypes)) {
            $this->throwInvalidData("Invalid attachmentable type: {$entityType}");
        }

        $entity = $entityType::where('id', $entityId)
            ->where('org_id', $attachment->org_id)
            ->first();

        if (!$entity) {
            $this->throwNotFound();
        }

        // Use the entity's attachments relationship to link the attachment
        $entity->attachments()->attach($attachment->id);
    }

    /**
     * Update an attachment.
     */
    public function updateAttachment(int $id, array $data): Model
    {
        $attachment = $this->findById($id);

        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $file = $data['file'];
            Storage::disk('public')->delete($attachment->file_path);
            $path = $file->store('attachments/'.date('Y/m'), 'public');
            $data = array_merge($data, [
                'filename' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'file_path' => $path,
            ]);
            unset($data['file']);
        }

        return $this->update($id, $data);
    }

    /**
     * Delete an attachment and its file.
     */
    public function deleteAttachment(int $id): void
    {
        $attachment = $this->findById($id);

        if ($attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $this->delete($id);
        }
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'category', 'file_type', 'description', 'filename', 'user_id', 
            'min_size', 'max_size', 'attachmentable_type', 'attachmentable_id',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return ['attachmentable', 'user'];
    }

    /**
     * Get attachment usage statistics
     */
    public function getAttachmentStats(int $attachmentId): array
    {
        $attachment = $this->findById($attachmentId);
        
        return [
            'items_count' => $attachment->items()->count(),
            'maintenances_count' => $attachment->maintenances()->count(), 
            'check_in_outs_count' => $attachment->checkInOuts()->count(),
            'total_usage' => DB::table('attachmentables')
                ->where('attachment_id', $attachmentId)
                ->count(),
            'is_orphaned' => DB::table('attachmentables')
                ->where('attachment_id', $attachmentId)
                ->count() === 0,
        ];
    }

    /**
     * attachment type options for UI, used this approach to avoid hardcoding in multiple places
     * the options can be scaled or modified easily
     */
    public function getAttachmentTypeOptions(): array
    {
        return [
            [
                'value' => 'App\\Models\\Item',
                'label' => 'Item'
            ],
            [
                'value' => 'App\\Models\\Maintenance', 
                'label' => 'Maintenance'
            ],
            [
                'value' => 'App\\Models\\CheckInOut',
                'label' => 'Check-In/Check-Out'
            ],
            [
                'value' => 'App\\Models\\Supplier',
                'label' => 'Supplier'
            ],
            [
                'value' => 'App\\Models\\Organization',
                'label' => 'Organization'
            ],
            [
                'value' => 'App\\Models\\User',
                'label' => 'User'
            ],
            [
                'value' => 'App\\Models\\Location',
                'label' => 'Location'
            ],
            [
                'value' => 'App\\Models\\Category',
                'label' => 'Category'
            ],
            [
                'value' => 'App\\Models\\ItemLocation',
                'label' => 'Item Location'
            ]
        ];
    }

    /**
     * Get all supported entity types that use the HasAttachments trait
     */
    public function getSupportedEntityTypes(): array
    {
        return [
            'App\\Models\\Item',
            'App\\Models\\Maintenance',
            'App\\Models\\CheckInOut',
            'App\\Models\\User',
            'App\\Models\\Organization',
            'App\\Models\\Location',
            'App\\Models\\Supplier',
            'App\\Models\\Category',
            'App\\Models\\ItemLocation'
        ];
    }
}
