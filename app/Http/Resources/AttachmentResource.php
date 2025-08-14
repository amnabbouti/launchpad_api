<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttachmentResource extends BaseResource {
    public function toArray(Request $request): array {
        $data = [
            'id'                => $this->id,
            'filename'          => $this->filename,
            'original_filename' => $this->original_filename,
            'file_type'         => $this->file_type,
            'file_type_name'    => $this->getFileTypeNameAttribute(),
            'extension'         => $this->extension,
            'size'              => $this->size,
            'human_size'        => $this->getHumanSizeAttribute(),
            'file_path'         => $this->file_path,
            'description'       => $this->description,
            'category'          => $this->category,
            'url'               => $this->getUrlAttribute(),

            // relationships
            'organization' => $this->whenLoaded('organization', fn () => [
                'id'   => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->getName(),
                'email' => $this->user?->email,
            ]),
            'date_uploaded' => $this->created_at?->format('c'),
            'updated_at'    => $this->updated_at?->format('c'),

            // Show if this attachment is orphaned
            'is_orphaned' => ! $this->relationLoaded('items')
                           && ! $this->relationLoaded('maintenances')
                           && ! $this->relationLoaded('checkInOuts') ? null : (
                               $this->items->isEmpty()
                               && $this->maintenances->isEmpty()
                               && $this->checkInOuts->isEmpty()
                           ),

            // Show what entities this attachment is connected to
            'attached_to' => [
                'items' => $this->whenLoaded(
                    'items',
                    fn () => $this->items->map(static fn ($item) => [
                        'id'   => $item->id,
                        'name' => $item->name,
                        'code' => $item->code,
                    ])->toArray(),
                ),
                'maintenances' => $this->whenLoaded(
                    'maintenances',
                    fn () => $this->maintenances->map(static fn ($maintenance) => [
                        'id'      => $maintenance->id,
                        'remarks' => $maintenance->remarks,
                        'cost'    => $maintenance->cost,
                    ])->toArray(),
                ),
                'check_in_outs' => $this->whenLoaded(
                    'checkInOuts',
                    fn () => $this->checkInOuts->map(static fn ($checkInOut) => [
                        'id'    => $checkInOut->id,
                        'type'  => $checkInOut->type,
                        'notes' => $checkInOut->notes,
                    ])->toArray(),
                ),
            ],
        ];

        return $this->addCommonData($data, $request);
    }
}
