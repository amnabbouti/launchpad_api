<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use DateTime;
use Illuminate\Http\Request;

class CheckInOutResource extends BaseResource {
    public function toArray(Request $request): array {
        $duration = null;
        if ($this->checkout_date && $this->checkin_date) {
            $start    = new DateTime($this->checkout_date);
            $end      = new DateTime($this->checkin_date);
            $duration = $start->diff($end)->days;
        }

        $data = [
            'id'                   => $this->id,
            'quantity'             => $this->quantity,
            'checkout_date'        => $this->checkout_date?->format('c'),
            'expected_return_date' => $this->expected_return_date?->format('c'),
            'checkin_date'         => $this->checkin_date?->format('c'),
            'checkin_quantity'     => $this->checkin_quantity,
            'duration_days'        => $duration,
            'reference'            => $this->reference,
            'notes'                => $this->notes,
            'is_active'            => $this->is_active,
            'is_checked_out'       => $this->is_checked_out,
            'is_checked_in'        => $this->is_checked_in,
            'is_overdue'           => $this->is_overdue,
            'user'                 => $this->whenLoaded('user', fn () => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'checkin_user' => $this->whenLoaded('checkinUser', fn () => [
                'id'    => $this->checkinUser?->id,
                'name'  => $this->checkinUser?->name,
                'email' => $this->checkinUser?->email,
            ]),
            'trackable' => $this->whenLoaded('trackable', fn () => [
                'id'   => $this->trackable?->id,
                'type' => class_basename($this->trackable_type),
                'item' => $this->trackable?->item ? [
                    'id'   => $this->trackable->item->id,
                    'name' => $this->trackable->item->name,
                    'code' => $this->trackable->item->code,
                ] : null,
                'location' => $this->trackable?->location ? [
                    'id'   => $this->trackable->location->id,
                    'name' => $this->trackable->location->name,
                    'code' => $this->trackable->location->code,
                ] : null,
                'quantity' => $this->trackable?->quantity,
            ]),
            'checkout_location' => $this->whenLoaded('checkoutLocation', fn () => [
                'id'   => $this->checkoutLocation?->id,
                'name' => $this->checkoutLocation?->name,
                'code' => $this->checkoutLocation?->code,
            ]),
            'checkin_location' => $this->whenLoaded('checkinLocation', fn () => [
                'id'   => $this->checkinLocation?->id,
                'name' => $this->checkinLocation?->name,
                'code' => $this->checkinLocation?->code,
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
