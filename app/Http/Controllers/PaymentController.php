<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create invoice for payment
     */
    public function createInvoice(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $result = $this->paymentService->createInvoice(
            $request->user(),
            $request->product_id,
            $request->variant_id,
            $request->quantity
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to create invoice',
                'error' => $result['error'] ?? 'Unknown error',
            ], 400);
        }

        $xenditResponse = $result['xendit_response'];

        return response()->json([
            'message' => 'Invoice created successfully',
            'data' => [
                'payment_id' => $result['payment']->id,
                'invoice_id' => $xenditResponse->getId(),
                'external_id' => $result['payment']->external_id,
                'amount' => $result['payment']->amount,
                'currency' => $result['payment']->currency,
                'invoice_url' => $xenditResponse->getInvoiceUrl(),
                'status' => $result['payment']->status,
                'expired_date' => $xenditResponse->getExpiryDate(),
            ]
        ], 201);
    }

    /**
     * Get invoice status by invoice ID
     */
    public function getInvoice(Request $request, $invoiceId)
    {
        $payment = Payment::where('invoice_id', $invoiceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $result = $this->paymentService->getInvoiceStatus($invoiceId);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to retrieve invoice',
                'error' => $result['error'] ?? 'Unknown error',
            ], 400);
        }

        $xenditInvoice = $result['invoice'];

        return response()->json([
            'message' => 'Invoice retrieved successfully',
            'data' => new PaymentResource($payment),
            'xendit_status' => [
                'id' => $xenditInvoice->getId(),
                'status' => $xenditInvoice->getStatus(),
                'amount' => $xenditInvoice->getAmount(),
                // 'paid_amount' => $xenditInvoice->getam(),
                'currency' => $xenditInvoice->getCurrency(),
                'created_at' => $xenditInvoice->getCreated(),
                'updated_at' => $xenditInvoice->getUpdated(),
                'expired_date' => $xenditInvoice->getExpiryDate(),
            ]
        ]);
    }

    /**
     * Get all payments for authenticated user
     */
    public function getPayments(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with(['product', 'variant'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'message' => 'Payments retrieved successfully',
            'data' => PaymentResource::collection($payments),
        ]);
    }

    /**
     * Expire invoice
     */
    public function expireInvoice(Request $request, $invoiceId)
    {
        $payment = Payment::where('invoice_id', $invoiceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $result = $this->paymentService->expireInvoice($invoiceId);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to expire invoice',
                'error' => $result['error'] ?? 'Unknown error',
            ], 400);
        }

        // Refresh payment from database
        $payment->refresh();

        return response()->json([
            'message' => 'Invoice expired successfully',
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Webhook handler for Xendit payment callback
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        $result = $this->paymentService->handleCallback($payload);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'],
            ], 404);
        }

        return response()->json([
            'message' => 'Webhook processed successfully',
            'data' => new PaymentResource($result['payment']),
        ]);
    }
}

