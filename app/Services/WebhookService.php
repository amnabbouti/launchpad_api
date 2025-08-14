<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\License;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WebhookService {
    public function handlePaymentFailed(array $payload): bool {
        $invoice    = $payload['data']['object'];
        $customerId = $invoice['customer'];

        return DB::transaction(static function () use ($customerId) {
            $organization = Organization::where('stripe_id', $customerId)->first();

            if (! $organization) {
                throw new InvalidArgumentException('Payment failed for unknown customer: ' . $customerId);
            }

            return true;
        });
    }

    public function handlePaymentSucceeded(array $payload): bool {
        $invoice    = $payload['data']['object'];
        $customerId = $invoice['customer'];

        return DB::transaction(static function () use ($invoice, $customerId) {
            $organization = Organization::where('stripe_id', $customerId)->first();

            if (! $organization) {
                throw new InvalidArgumentException('Payment succeeded for unknown customer: ' . $customerId);
            }

            $invoiceId = $invoice['id'];
            $license   = License::where('org_id', $organization->id)
                ->whereJsonContains('meta->stripe_invoice_id', $invoiceId)
                ->first();

            if ($license) {
                $updateData = ['status' => 'active'];

                if (! $license->starts_at || $license->starts_at->isFuture()) {
                    $updateData['starts_at'] = now();
                }

                $license->update($updateData);

                if ($organization->license_id !== $license->id) {
                    $organization->license_id = $license->id;
                    $organization->save();
                }
            }

            return true;
        });
    }
}
