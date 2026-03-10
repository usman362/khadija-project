<?php

namespace App\Http\Controllers\Webhook;

use App\Domain\Payments\Services\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * Handle Stripe webhook.
     */
    public function stripe(Request $request): Response
    {
        $gateway = $this->paymentService->getGateway('stripe');
        $headers = $request->headers->all();
        $rawBody = $request->getContent();

        if (! $gateway->verifyWebhook($headers, $rawBody)) {
            Log::warning('Stripe webhook signature verification failed');
            return response('Invalid signature', 400);
        }

        try {
            $payload = json_decode($rawBody, true);
            $gateway->processWebhook($payload);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('OK', 200); // Return 200 to avoid retries
        }
    }

    /**
     * Handle PayPal webhook.
     */
    public function paypal(Request $request): Response
    {
        $gateway = $this->paymentService->getGateway('paypal');
        $headers = $request->headers->all();
        $rawBody = $request->getContent();

        if (! $gateway->verifyWebhook($headers, $rawBody)) {
            Log::warning('PayPal webhook signature verification failed');
            return response('Invalid signature', 400);
        }

        try {
            $payload = json_decode($rawBody, true);
            $gateway->processWebhook($payload);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('PayPal webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('OK', 200);
        }
    }
}
