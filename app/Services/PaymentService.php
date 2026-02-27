<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;
use Xendit\XenditSdkException;

class PaymentService
{
    protected InvoiceApi $invoiceApi;

    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $this->invoiceApi = new InvoiceApi();
    }

    /**
     * Create invoice for payment
     */
    public function createInvoice($user, $productId, $variantId = null, $quantity = 1)
    {
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        // Calculate amount
        $price = $variant && $variant->price ? $variant->price : $product->price;
        $amount = (int)($price * $quantity);

        // Create external_id (unique identifier)
        $externalId = 'payment-' . $user->id . '-' . time() . '-' . Str::random(6);

        // Create description
        $description = "{$product->name}";
        if ($variant) {
            $description .= " ({$variant->size} / {$variant->color})";
        }
        $description .= " x {$quantity}";

        try {
            // Create invoice request
            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => $externalId,
                'description' => $description,
                'amount' => $amount,
                'invoice_duration' => 86400, // 24 hours
                'currency' => 'IDR',
                'reminder_time' => 1,
            ]);

            // Call Xendit API
            $xenditResponse = $this->invoiceApi->createInvoice($createInvoiceRequest);

            // Save payment record to database
            $payment = Payment::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'external_id' => $externalId,
                'invoice_id' => $xenditResponse->getId(),
                'description' => $description,
                'amount' => $amount,
                'currency' => 'IDR',
                'status' => 'PENDING',
                'xendit_response' => json_decode(json_encode($xenditResponse), true),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'xendit_response' => $xenditResponse,
            ];

        } catch (XenditSdkException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'full_error' => $e->getFullError(),
            ];
        }
    }

    /**
     * Get invoice status from Xendit
     */
    public function getInvoiceStatus($invoiceId)
    {
        try {
            $xenditInvoice = $this->invoiceApi->getInvoiceById($invoiceId);

            return [
                'success' => true,
                'invoice' => $xenditInvoice,
            ];

        } catch (XenditSdkException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Expire invoice
     */
    public function expireInvoice($invoiceId)
    {
        try {
            $expiredInvoice = $this->invoiceApi->expireInvoice($invoiceId);

            // Update payment status in database
            Payment::where('invoice_id', $invoiceId)->update([
                'status' => 'EXPIRED',
            ]);

            return [
                'success' => true,
                'invoice' => $expiredInvoice,
            ];

        } catch (XenditSdkException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook callback from Xendit
     */
    public function handleCallback($payload)
    {
        $externalId = $payload['external_id'] ?? null;
        $payment = Payment::where('external_id', $externalId)->first();

        if (!$payment) {
            return [
                'success' => false,
                'error' => 'Payment not found',
            ];
        }

        // Update payment status
        $payment->update([
            'status' => $payload['status'] ?? 'PENDING',
            'invoice_id' => $payload['id'] ?? $payment->invoice_id,
            'xendit_response' => array_merge($payment->xendit_response ?? [], $payload),
        ]);

        return [
            'success' => true,
            'payment' => $payment,
        ];
    }
}
