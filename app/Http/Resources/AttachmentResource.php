<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttachmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->public_id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'file_type' => $this->file_type,
            'file_type_name' => $this->getFileTypeNameAttribute(),
            'extension' => $this->extension,
            'size' => $this->size,
            'human_size' => $this->getHumanSizeAttribute(),
            'file_path' => $this->file_path,
            'description' => $this->description,
            'category' => $this->category,
            'url' => $this->getUrlAttribute(),

            // relationships
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->public_id,
                'name' => $this->organization?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->public_id,
                'name' => $this->user?->getName(),
                'email' => $this->user?->email,
            ]),
            'date_uploaded' => $this->created_at?->format('c'),
            'updated_at' => $this->updated_at?->format('c'),

            // Show if this attachment is orphaned
            'is_orphaned' => ! $this->relationLoaded('items') &&
                           ! $this->relationLoaded('maintenances') &&
                           ! $this->relationLoaded('checkInOuts') ? null : (
                               $this->items->isEmpty() &&
                               $this->maintenances->isEmpty() &&
                               $this->checkInOuts->isEmpty()
                           ),

            // Show what entities this attachment is connected to
            'attached_to' => [
                               'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                                   'id' => $item->public_id,
                                   'name' => $item->name,
                                   'code' => $item->code,
                               ])
                               ),
                               'maintenances' => $this->whenLoaded('maintenances', fn () => $this->maintenances->map(fn ($maintenance) => [
                                   'id' => $maintenance->public_id,
                                   'remarks' => $maintenance->remarks,
                                   'cost' => $maintenance->cost,
                               ])
                               ),
                               'check_in_outs' => $this->whenLoaded('checkInOuts', fn () => $this->checkInOuts->map(fn ($checkInOut) => [
                                   'id' => $checkInOut->public_id,
                                   'type' => $checkInOut->type,
                                   'notes' => $checkInOut->notes,
                               ])
                               ),
                           ],
        ];

        return $this->addCommonData($data, $request);
    }
}
