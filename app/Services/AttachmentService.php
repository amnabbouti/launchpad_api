<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService extends BaseService
{
    public function __construct(Attachment $attachment)
    {
        parent::__construct($attachment);
    }

    /**
     * Process and standardize request parameters.
     */
    public function processRequestParams(array $params): array
    {
        $processed = [];

        // Process relationships
        if (! empty($params['with'])) {
            $processed['with'] = is_string($params['with'])
                ? array_intersect(explode(',', $params['with']), ['attachmentable', 'user'])
                : array_intersect($params['with'], ['attachmentable', 'user']);
        }

        // Process text filters
        $textFilters = ['category', 'file_type', 'description', 'filename'];

        foreach ($textFilters as $filter) {
            if (! empty($params[$filter])) {
                $processed[$filter] = $params[$filter];
            }
        }

        // Process numeric filters
        $numericFilters = ['user_id', 'min_size', 'max_size'];

        foreach ($numericFilters as $filter) {
            if (isset($params[$filter]) && is_numeric($params[$filter])) {
                $processed[$filter] = $params[$filter];
            }
        }

        // Process attachmentable filters
        if (! empty($params['attachmentable_type']) && ! empty($params['attachmentable_id'])) {
            $processed['attachmentable_type'] = $params['attachmentable_type'];
            $processed['attachmentable_id'] = $params['attachmentable_id'];
        }

        return $processed;
    }

    /**
     * Get filtered attachments with organization scoping.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Process filters
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['file_type'])) {
            $query->where('file_type', 'like', '%'.$filters['file_type'].'%');
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['min_size'])) {
            $query->where('size', '>=', $filters['min_size']);
        }

        if (! empty($filters['max_size'])) {
            $query->where('size', '<=', $filters['max_size']);
        }

        if (! empty($filters['description'])) {
            $query->where('description', 'like', '%'.$filters['description'].'%');
        }

        if (! empty($filters['filename'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('filename', 'like', '%'.$filters['filename'].'%')
                    ->orWhere('original_filename', 'like', '%'.$filters['filename'].'%');
            });
        }

        if (! empty($filters['attachmentable_type']) && ! empty($filters['attachmentable_id'])) {
            $query->whereHas('attachmentable', function ($q) use ($filters) {
                $q->where('attachmentable_type', $filters['attachmentable_type'])
                    ->where('attachmentable_id', $filters['attachmentable_id']);
            });
        }

        // Process relationships
        if (! empty($filters['with'])) {
            $query->with($filters['with']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Find attachment by ID with proper exception handling.
     */
    public function findById(int $id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        // Validate relations for attachment service
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

        return $this->create($data);
    }

    /**
     * Update an attachment with validated data.
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
}
