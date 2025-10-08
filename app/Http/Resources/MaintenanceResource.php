<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use DateTime;
use Illuminate\Http\Request;

class MaintenanceResource extends BaseResource {
    public function toArray(Request $request): array {
        // Calculate if maintenance is currently active
        $isActive = $this->date_in_maintenance && ! $this->date_back_from_maintenance;

        // Calculate duration if maintenance is completed
        $duration = null;
        if ($this->date_in_maintenance && $this->date_back_from_maintenance) {
            $start    = new DateTime($this->date_in_maintenance instanceof \Carbon\Carbon ? $this->date_in_maintenance->toDateTimeString() : $this->date_in_maintenance);
            $end      = new DateTime($this->date_back_from_maintenance instanceof \Carbon\Carbon ? $this->date_back_from_maintenance->toDateTimeString() : $this->date_back_from_maintenance);
            $duration = $start->diff($end)->days;
        }

        $data = [
            'id'                                  => $this->id,
            'remarks'                             => $this->remarks,
            'cost'                                => $this->cost,
            'date_in_maintenance'                 => $this->date_in_maintenance?->format('c'),
            'date_expected_back_from_maintenance' => $this->date_expected_back_from_maintenance?->format('c'),
            'date_back_from_maintenance'          => $this->date_back_from_maintenance?->format('c'),
            'duration_days'                       => $duration,
            'is_active'                           => $isActive,
            'is_repair'                           => $this->is_repair,
            'import_id'                           => $this->import_id,
            'import_source'                       => $this->import_source,

            // Relationships
            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'maintainable' => $this->whenLoaded('maintainable', function () {
                if ($this->maintainable instanceof \App\Models\Item) {
                    return [
                        'id'   => $this->maintainable->id,
                        'name' => $this->maintainable->name,
                        'code' => $this->maintainable->code,
                        'type' => 'item',
                    ];
                }

                // Handle other maintainable types if they exist
                return [
                    'id'   => $this->maintainable?->id,
                    'name' => $this->maintainable?->name ?? 'Unknown',
                    'type' => class_basename($this->maintainable_type),
                ];
            }),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id'    => $this->supplier?->id,
                'name'  => $this->supplier?->name,
                'email' => $this->supplier?->email,
            ]),
            'status_out' => $this->whenLoaded('statusOut', fn () => [
                'id'    => $this->statusOut?->id,
                'name'  => $this->statusOut?->name,
                'color' => $this->statusOut?->color,
            ]),
            'status_in' => $this->whenLoaded('statusIn', fn () => [
                'id'    => $this->statusIn?->id,
                'name'  => $this->statusIn?->name,
                'color' => $this->statusIn?->color,
            ]),
            'maintenance_details' => $this->whenLoaded(
                'maintenanceDetails',
                fn () => $this->maintenanceDetails ? $this->maintenanceDetails->map(static fn ($detail) => [
                    'id'                       => $detail->id,
                    'value'                    => $detail->value,
                    'maintenance_condition_id' => $detail->maintenance_condition_id,
                ]) : [],
            ),
            'attachments' => $this->whenLoaded(
                'attachments',
                fn () => $this->attachments->map(static fn ($attachment) => [
                    'id'   => $attachment->id,
                    'name' => $attachment->name,
                    'url'  => $attachment->url,
                    'size' => $attachment->size,
                ]),
            ),
        ];

        return $this->addCommonData($data, $request);
    }
}
