<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\ItemLocation;
use App\Models\StockItem;
use Illuminate\Http\Request;

class CheckInOutResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->public_id,
            'org_id' => $this->org_id,
            'user_id' => $this->user_id,
            'checkout_location_id' => $this->checkout_location_id,
            'checkout_date' => $this->checkout_date?->toISOString(),
            'quantity' => $this->quantity,
            'status_out_id' => $this->status_out_id,
            'checkin_user_id' => $this->checkin_user_id,
            'checkin_location_id' => $this->checkin_location_id,
            'checkin_date' => $this->checkin_date?->toISOString(),
            'checkin_quantity' => $this->checkin_quantity,
            'status_in_id' => $this->status_in_id,
            'expected_return_date' => $this->expected_return_date?->toISOString(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'is_checked_in' => $this->is_checked_in,
            'is_overdue' => $this->is_overdue,
            'is_checked_out' => $this->is_checked_out,
            'organization' => $this->whenLoaded('organization', fn () => new OrganizationResource($this->organization)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'checkinUser' => $this->whenLoaded('checkinUser', fn () => new UserResource($this->checkinUser)),
            'trackable' => $this->whenLoaded('trackable', function () {
                if ($this->trackable instanceof ItemLocation) {
                    return new ItemLocationResource($this->trackable);
                } elseif ($this->trackable instanceof Item) {
                    return new ItemResource($this->trackable);
                } elseif ($this->trackable instanceof StockItem) {
                    return new StockItemResource($this->trackable);
                }
                return null;
            }),
            'checkoutLocation' => $this->whenLoaded('checkoutLocation', fn () => new LocationResource($this->checkoutLocation)),
            'checkinLocation' => $this->whenLoaded('checkinLocation', fn () => new LocationResource($this->checkinLocation)),
            'statusOut' => $this->whenLoaded('statusOut', fn () => new ItemStatusResource($this->statusOut)),
            'statusIn' => $this->whenLoaded('statusIn', fn () => new ItemStatusResource($this->statusIn)),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
        ];

        return $this->addCommonData($data, $request);
    }
}
