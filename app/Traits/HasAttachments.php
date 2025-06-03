<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasAttachments
{
    /**
     * Get all of the model's attachments.
     */
    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachmentable', 'attachmentables')
            ->where(function ($query) {
                // Ensure organization separation for multi-tenant support
                if (isset($this->org_id)) {
                    $query->where('org_id', $this->org_id);
                }
            });
    }

    /**
     * Attach a file to the model.
     */
    public function attachFile(UploadedFile $file, ?string $category = null, ?string $description = null): Attachment
    {
        // Get organization ID from the model for multi-tenant support
        $organizationId = $this->org_id ?? Auth::user()->org_id ?? null;

        if (! $organizationId) {
            throw new \Exception('Organization ID is required for file attachments');
        }

        // Generate a unique filename
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        // Store the file in an organization
        $path = $file->storeAs(
            'attachments/org_'.$organizationId.'/'.$this->getTable().'/'.$this->id,
            $filename,
            'public',
        );

        // Create the attachment record
        $attachment = new Attachment([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'file_path' => $path,
            'description' => $description,
            'category' => $category,
            'user_id' => Auth::id(),
            'org_id' => $organizationId,
        ]);

        $attachment->save();

        // Attach the file
        $this->attachments()->attach($attachment->id);

        return $attachment;
    }

    /**
     * Detach a file from the model.
     */
    public function detachFile(int $attachmentId, bool $deleteFile = true): bool
    {
        // Get organization ID from the model for multi-tenant support
        $organizationId = $this->org_id ?? Auth::user()->org_id ?? null;

        $attachment = Attachment::where('id', $attachmentId)
            ->where('org_id', $organizationId)
            ->first();

        if (! $attachment) {
            return false;
        }

        // Detach the file from the model
        $this->attachments()->detach($attachmentId);

        // If deleteFile is true, check if we should delete the physical file too
        if ($deleteFile) {
            $attachedToItems = $attachment->items()->count();
            $attachedToMaintenances = $attachment->maintenances()->count();
            $attachedToCheckInOuts = $attachment->checkInOuts()->count();
            $totalAttached = $attachedToItems + $attachedToMaintenances + $attachedToCheckInOuts;

            if ($totalAttached === 0) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        $this->attachments()->detach($attachmentId);

        return true;
    }

    /**
     * Get attachments by category.
     */
    public function getAttachmentsByCategory(string $category)
    {
        return $this->attachments()->where('category', $category)->get();
    }

    /**
     * Check if the model has any attachments.
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->count() > 0;
    }

    /**
     * Count the model's attachments.
     */
    public function countAttachments(): int
    {
        return $this->attachments()->count();
    }
}
