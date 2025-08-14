<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\AppConstants;
use App\Constants\ErrorMessages;
use App\Models\Attachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

use function in_array;

class AttachmentService extends BaseService {
    public function __construct(Attachment $attachment) {
        parent::__construct($attachment);
    }

    /**
     * Create a new attachment with file upload.
     */
    public function createAttachment(array $data): Model {
        $data = $this->applyAttachmentBusinessRules($data);
        $this->validateAttachmentBusinessRules($data);

        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $file = $data['file'];
            $path = $file->store('attachments/' . date('Y/m'), 'public');
            $data = array_merge($data, [
                'filename'          => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $file->getClientOriginalName(),
                'file_type'         => $file->getMimeType(),
                'extension'         => $file->getClientOriginalExtension(),
                'size'              => $file->getSize(),
                'file_path'         => $path,
            ]);
            unset($data['file']);
        }

        $attachment = $this->create($data);

        // If ok link the attachment
        if (isset($data['attachmentable_type'], $data['attachmentable_id'])) {
            $this->linkAttachmentToEntity($attachment, $data['attachmentable_type'], $data['attachmentable_id']);
        }

        return $attachment;
    }

    /**
     * Delete an attachment and its file.
     */
    public function deleteAttachment(string $id): void {
        $attachment = $this->findById($id);

        Storage::disk('public')->delete($attachment->file_path);
        $this->delete($id);
    }

    /**
     * Find attachment by ID
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model {
        // Load default relationships for better API responses
        $defaultRelations = ['user', 'items', 'maintenances', 'checkInOuts'];

        if (! empty($relations)) {
            $relations = array_intersect($relations, ['attachmentable', 'user', 'items', 'maintenances', 'checkInOuts']);
        } else {
            $relations = $defaultRelations;
        }

        return parent::findById($id, $columns, $relations, $appends);
    }

    /**
     * Get attachment usage statistics
     */
    public function getAttachmentStats(string $attachmentId): array {
        $attachment = $this->findById($attachmentId);

        if (! $attachment instanceof Attachment) {
            throw new InvalidArgumentException(__(ErrorMessages::ATTACHMENT_NOT_FOUND));
        }

        return [
            'items_count'         => $attachment->items()->count(),
            'maintenances_count'  => $attachment->maintenances()->count(),
            'check_in_outs_count' => $attachment->checkInOuts()->count(),
            'total_usage'         => DB::table('attachmentables')
                ->where('attachment_id', $attachmentId)
                ->count(),
            'is_orphaned' => DB::table('attachmentables')
                ->where('attachment_id', $attachmentId)
                ->count() === 0,
        ];
    }

    /**
     * Get attachment type options for UI
     */
    public function getAttachmentTypeOptions(): array {
        return cache()->remember('attachment_type_options', 3600, static fn () => [
            ['value' => 'App\\Models\\Item', 'label' => 'Item'],
            ['value' => 'App\\Models\\Maintenance', 'label' => 'Maintenance'],
            ['value' => 'App\\Models\\CheckInOut', 'label' => 'Check-In/Check-Out'],
            ['value' => 'App\\Models\\Supplier', 'label' => 'Supplier'],
            ['value' => 'App\\Models\\Organization', 'label' => 'Organization'],
            ['value' => 'App\\Models\\User', 'label' => 'User'],
            ['value' => 'App\\Models\\Location', 'label' => 'Location'],
            ['value' => 'App\\Models\\Category', 'label' => 'Category'],
            ['value' => 'App\\Models\\ItemLocation', 'label' => 'Item Location'],
        ]);
    }

    /**
     * Get filtered attachments
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        // Load default relationships for better API responses
        $defaultRelations = ['user', 'items', 'maintenances', 'checkInOuts'];
        $query->with($defaultRelations);

        $query->when($filters['category'] ?? null, static fn ($q, $value) => $q->where('category', $value))
            ->when($filters['file_type'] ?? null, static fn ($q, $value) => $q->where('file_type', 'like', "%{$value}%"))
            ->when($filters['user_id'] ?? null, static fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['min_size'] ?? null, static fn ($q, $value) => $q->where('size', '>=', $value))
            ->when($filters['max_size'] ?? null, static fn ($q, $value) => $q->where('size', '<=', $value))
            ->when($filters['description'] ?? null, static fn ($q, $value) => $q->where('description', 'like', "%{$value}%"))
            ->when($filters['filename'] ?? null, static function ($q, $value) {
                return $q->where(static function ($subQuery) use ($value): void {
                    $subQuery->where('filename', 'like', "%{$value}%")
                        ->orWhere('original_filename', 'like', "%{$value}%");
                });
            })
            ->when(
                $filters['attachmentable_type'] ?? null && $filters['attachmentable_id'] ?? null,
                static function ($q) use ($filters) {
                    return $q->whereHas('attachmentable', static function ($subQuery) use ($filters): void {
                        $subQuery->where('attachmentable_type', $filters['attachmentable_type'])
                            ->where('attachmentable_id', $filters['attachmentable_id']);
                    });
                },
            )
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations))
            ->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Get all supported entity types using caching
     */
    public function getSupportedEntityTypes(): array {
        return cache()->remember('attachment_supported_entity_types', 3600, static fn () => [
            'App\\Models\\Item',
            'App\\Models\\Maintenance',
            'App\\Models\\CheckInOut',
            'App\\Models\\User',
            'App\\Models\\Organization',
            'App\\Models\\Location',
            'App\\Models\\Supplier',
            'App\\Models\\Category',
            'App\\Models\\ItemLocation',
        ]);
    }

    /**
     * Link an attachment to a specific entity
     */
    public function linkAttachmentToEntity(Model $attachment, string $entityType, string $entityId): void {
        $supportedTypes = $this->getSupportedEntityTypes();

        if (! in_array($entityType, $supportedTypes, true)) {
            $this->throwInvalidData("Invalid attachmentable type: {$entityType}");
        }

        $entity = $entityType::where('id', $entityId)->first();

        if (! $entity) {
            $this->throwNotFound();
        }

        // Using an entity's attachment relationship to link the attachment
        $entity->attachments()->attach($attachment->id);
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'category'            => $this->toString($params['category'] ?? null),
            'file_type'           => $this->toString($params['file_type'] ?? null),
            'description'         => $this->toString($params['description'] ?? null),
            'filename'            => $this->toString($params['filename'] ?? null),
            'user_id'             => $this->toInt($params['user_id'] ?? null),
            'min_size'            => $this->toInt($params['min_size'] ?? null),
            'max_size'            => $this->toInt($params['max_size'] ?? null),
            'attachmentable_type' => $this->toString($params['attachmentable_type'] ?? null),
            'attachmentable_id'   => $this->toInt($params['attachmentable_id'] ?? null),
            'with'                => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Update an attachment.
     */
    public function updateAttachment(string $id, array $data): Model {
        $data = $this->applyAttachmentBusinessRules($data);
        $this->validateAttachmentBusinessRules($data, $id);

        $attachment = $this->findById($id);

        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $file = $data['file'];
            Storage::disk('public')->delete($attachment->file_path);
            $path = $file->store('attachments/' . date('Y/m'), 'public');
            $data = array_merge($data, [
                'filename'          => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $file->getClientOriginalName(),
                'file_type'         => $file->getMimeType(),
                'extension'         => $file->getClientOriginalExtension(),
                'size'              => $file->getSize(),
                'file_path'         => $path,
            ]);
            unset($data['file']);
        }

        $updatedAttachment = $this->update($id, $data);

        if (isset($data['attachmentable_type'], $data['attachmentable_id'])) {
            $this->linkAttachmentToEntity($updatedAttachment, $data['attachmentable_type'], $data['attachmentable_id']);
        }

        return $updatedAttachment;
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'category',
            'file_type',
            'description',
            'filename',
            'user_id',
            'min_size',
            'max_size',
            'attachmentable_type',
            'attachmentable_id',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array {
        return ['attachmentable', 'user'];
    }

    /**
     * Apply business rules for attachment operations.
     */
    private function applyAttachmentBusinessRules(array $data): array {
        if (! isset($data['user_id'])) {
            $data['user_id'] = AuthorizationEngine::getCurrentUser()?->id;
        }

        return $data;
    }

    /**
     * Validate business rules for attachment operations.
     */
    private function validateAttachmentBusinessRules(array $data, $attachmentId = null): void {
        $isUpdate = $attachmentId !== null;

        if (! $isUpdate) {
            $requiredFields = [
                'attachmentable_type' => __(ErrorMessages::ATTACHMENT_ENTITY_REQUIRED),
                'attachmentable_id'   => __(ErrorMessages::ATTACHMENT_ENTITY_REQUIRED),
            ];

            foreach ($requiredFields as $field => $errorMessage) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException(__($errorMessage));
                }
            }
        }

        $isUpdate = $attachmentId !== null;

        if (! $isUpdate && ! isset($data['file'])) {
            throw new InvalidArgumentException(__(ErrorMessages::ATTACHMENT_FILE_REQUIRED));
        }

        if (isset($data['file'])) {
            $file = $data['file'];

            if (! ($file instanceof UploadedFile)) {
                throw new InvalidArgumentException(__(ErrorMessages::ATTACHMENT_FILE_INVALID));
            }

            if ($file->getSize() > AppConstants::MAX_UPLOAD_SIZE) {
                $maxSizeMB = AppConstants::MAX_UPLOAD_SIZE / 1024 / 1024;

                throw new InvalidArgumentException(__(ErrorMessages::ATTACHMENT_FILE_TOO_LARGE, ['max_size' => $maxSizeMB]));
            }

            if (! in_array($file->getMimeType(), AppConstants::SUPPORTED_ATTACHMENT_MIME_TYPES, true)) {
                $allowedTypes = implode(', ', AppConstants::SUPPORTED_ATTACHMENT_EXTENSIONS);

                throw new InvalidArgumentException(__(ErrorMessages::ATTACHMENT_FILE_TYPE_INVALID, ['allowed_types' => $allowedTypes]));
            }
        }

        if (isset($data['attachmentable_type'])) {
            $supportedTypes = $this->getSupportedEntityTypes();
            if (! in_array($data['attachmentable_type'], $supportedTypes, true)) {
                throw new InvalidArgumentException(__(ErrorMessages::ATTACHMENT_ENTITY_INVALID));
            }
        }
    }
}
