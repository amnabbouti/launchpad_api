<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\License;
use App\Models\Organization;
use Stripe\StripeClient;

use function sprintf;

class LicensePaymentService {
    private StripeClient $stripe;

    public function __construct() {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create and send an invoice for the given license.
     */
    public function createInvoiceForLicense(License $license): array {
        $organization = Organization::findOrFail($license->org_id);

        if (! $organization->stripe_id) {
            $customer = $this->stripe->customers->create([
                'name'     => $organization->name,
                'email'    => $organization->email,
                'metadata' => [
                    'org_id' => $organization->id,
                ],
            ]);
            $organization->stripe_id = $customer->id;
            $organization->save();
        }

        $amountCents = (int) round(((float) ($license->price ?? 0)) * 100) * max(1, (int) $license->seats);
        $currency    = (string) config('services.stripe.currency', 'eur');

        // Create invoice item
        $this->stripe->invoiceItems->create([
            'customer'    => $organization->stripe_id,
            'amount'      => $amountCents,
            'currency'    => $currency,
            'description' => sprintf('License %d (%d seats)', $license->id, (int) $license->seats),
            'metadata'    => [
                'license_id' => $license->id,
                'org_id'     => $organization->id,
            ],
        ]);

        // Create an invoice with metadata and send
        $invoice = $this->stripe->invoices->create([
            'customer'                       => $organization->stripe_id,
            'collection_method'              => 'send_invoice',
            'days_until_due'                 => (int) config('services.stripe.days_until_due', 7),
            'pending_invoice_items_behavior' => 'include',
            'metadata'                       => [
                'license_id' => $license->id,
                'org_id'     => $organization->id,
            ],
        ]);

        $finalized = $this->stripe->invoices->finalizeInvoice($invoice->id, []);
        if ($finalized->status === 'open') {
            $this->stripe->invoices->sendInvoice($finalized->id, []);
        }

        $license->meta = array_merge($license->meta ?? [], [
            'stripe_invoice_id'  => $finalized->id,
            'hosted_invoice_url' => $finalized->hosted_invoice_url ?? null,
            'amount_cents'       => $amountCents,
            'currency'           => $currency,
        ]);
        $license->save();

        return [
            'invoice_id'         => $finalized->id,
            'hosted_invoice_url' => $finalized->hosted_invoice_url,
        ];
    }

    public function processInvoiceFlow(License $license): array {
        if ($license->status === 'active') {
            return ['code' => 'already_active', 'payload' => $license];
        }

        $meta              = $license->meta ?? [];
        $existingInvoiceId = $meta['stripe_invoice_id'] ?? null;

        if ($existingInvoiceId) {
            $invoice = $this->stripe->invoices->retrieve($existingInvoiceId, []);

            if (($invoice->status === 'paid') || ($invoice->paid === true)) {
                /** @var LicenseService $licenseService */
                $licenseService = app(LicenseService::class);
                $updated        = $licenseService->activateLicense($license->id);

                return ['code' => 'activated', 'payload' => $updated];
            }

            return [
                'code'    => 'invoice_pending',
                'payload' => [
                    'invoice_id'         => $existingInvoiceId,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url ?? ($meta['hosted_invoice_url'] ?? null),
                    'status'             => $invoice->status,
                    'paid'               => $invoice->paid ?? false,
                ],
            ];
        }

        $created = $this->createInvoiceForLicense($license);

        return ['code' => 'invoice_created', 'payload' => $created];
    }
}
