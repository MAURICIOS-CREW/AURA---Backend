<?php

namespace App\Services;

use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected string $secretKey;
    protected string $publishableKey;
    protected string $currency;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret') ?? env('STRIPE_SECRET', '');
        $this->publishableKey = config('services.stripe.key') ?? env('STRIPE_KEY', '');
        $this->currency = config('services.stripe.currency') ?? 'mxn';

        if (!empty($this->secretKey)) {
            Stripe::setApiKey($this->secretKey);
        }
    }

    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * Crear un PaymentIntent en Stripe para PaymentSheet (o confirmación móvil).
     */
    public function createPaymentIntent(float $amount, array $metadata = [], ?string $currency = null): PaymentIntent
    {
        $cents = (int) round($amount * 100);
        $curr = strtolower($currency ?? $this->currency);

        return PaymentIntent::create([
            'amount' => $cents,
            'currency' => $curr,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => $metadata,
        ]);
    }

    /**
     * Obtener el estado actual de un PaymentIntent por ID (pi_xxx).
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Crear y confirmar un PaymentIntent utilizando un PaymentMethod directo (pm_xxx).
     */
    public function createAndConfirmPaymentIntent(float $amount, string $paymentMethodId, array $metadata = [], ?string $currency = null): PaymentIntent
    {
        $cents = (int) round($amount * 100);
        $curr = strtolower($currency ?? $this->currency);

        return PaymentIntent::create([
            'amount' => $cents,
            'currency' => $curr,
            'payment_method' => $paymentMethodId,
            'confirm' => true,
            'off_session' => false,
            'return_url' => config('app.url', 'http://localhost'),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Procesar o verificar la transacción de pago con Stripe o definir el estado para pagos manuales/transferencia.
     */
    public function processPaymentTransaction(
        float $amount,
        string $paymentMethod = 'stripe',
        ?string $stripePaymentIntentId = null,
        ?string $stripePaymentMethodId = null,
        array $metadata = []
    ): array {
        $isStripe = strtolower($paymentMethod) === 'stripe' || !empty($stripePaymentIntentId) || !empty($stripePaymentMethodId);

        if (!$isStripe) {
            return [
                'status' => strtolower($paymentMethod) === 'transfer' ? 'pending' : 'approved',
                'payment_intent_id' => null,
                'payment_method_id' => null,
                'receipt_url' => null,
                'failure_code' => null,
                'failure_reason' => null,
                'user_message' => null,
            ];
        }

        try {
            $intent = $this->resolvePaymentIntent($amount, $stripePaymentIntentId, $stripePaymentMethodId, $metadata);

            if ($intent) return $this->mapIntentStatus($intent, $stripePaymentMethodId);
        } catch (\Throwable $e) {
            $errorInfo = $this->parseStripeException($e);

            return [
                'status' => 'refused',
                'payment_intent_id' => $stripePaymentIntentId,
                'payment_method_id' => $stripePaymentMethodId,
                'receipt_url' => null,
                'failure_code' => $errorInfo['failure_code'],
                'failure_reason' => $errorInfo['failure_reason'],
                'user_message' => $errorInfo['user_message'],
            ];
        }

        return [
            'status' => 'approved',
            'payment_intent_id' => $stripePaymentIntentId,
            'payment_method_id' => $stripePaymentMethodId,
            'receipt_url' => null,
            'failure_code' => null,
            'failure_reason' => null,
            'user_message' => null,
        ];
    }

    /**
     * Resolver/obtener el PaymentIntent según los parámetros disponibles.
     */
    private function resolvePaymentIntent(
        float $amount,
        ?string $stripePaymentIntentId,
        ?string $stripePaymentMethodId,
        array $metadata
    ): ?PaymentIntent {
        if (!empty($stripePaymentIntentId)) {
            return $this->retrievePaymentIntent($stripePaymentIntentId);
        }

        if (!empty($stripePaymentMethodId)) {
            return $this->createAndConfirmPaymentIntent($amount, $stripePaymentMethodId, $metadata);
        }

        return null;
    }

    /**
     * Mapear el estado del PaymentIntent a una estructura limpia de respuesta.
     */
    private function mapIntentStatus(PaymentIntent $intent, ?string $fallbackPaymentMethodId): array
    {
        $receiptUrl = $intent->latest_charge?->receipt_url ?? $intent->charges?->data[0]?->receipt_url ?? null;
        $paymentMethodId = $intent->payment_method ?? $fallbackPaymentMethodId;

        return match ($intent->status) {
            'succeeded' => [
                'status' => 'approved',
                'payment_intent_id' => $intent->id,
                'payment_method_id' => $paymentMethodId,
                'receipt_url' => $receiptUrl,
                'failure_code' => null,
                'failure_reason' => null,
                'user_message' => null,
            ],
            'requires_action', 'processing' => [
                'status' => 'pending',
                'payment_intent_id' => $intent->id,
                'payment_method_id' => $paymentMethodId,
                'receipt_url' => $receiptUrl,
                'failure_code' => null,
                'failure_reason' => null,
                'user_message' => null,
            ],
            default => [
                'status' => 'refused',
                'payment_intent_id' => $intent->id,
                'payment_method_id' => $paymentMethodId,
                'receipt_url' => $receiptUrl,
                'failure_code' => $intent->status,
                'failure_reason' => "Estado de Stripe PaymentIntent: {$intent->status}",
                'user_message' => "El pago no pudo ser completado en Stripe (Estado: {$intent->status}).",
            ],
        };
    }

    /**
     * Formatear excepciones de Stripe a mensajes claros de rechazo.
     */
    public function parseStripeException(\Throwable $e): array
    {
        $failureCode = 'stripe_error';
        $failureReason = $e->getMessage();
        $userMessage = 'No se pudo procesar el pago con Stripe. Por favor intente nuevamente o use otra tarjeta.';

        if ($e instanceof CardException) {
            $failureCode = $e->getDeclineCode() ?? $e->getStripeCode() ?? 'card_declined';
            $failureReason = "Tarjeta rechazada por Stripe: " . $e->getMessage();

            $messages = [
                'insufficient_funds' => 'La tarjeta no cuenta con fondos suficientes.',
                'expired_card'       => 'La tarjeta ha expirado.',
                'incorrect_cvc'      => 'El código de seguridad (CVC) es incorrecto.',
                'processing_error'   => 'Ocurrió un error al procesar la tarjeta. Por favor reintente.',
            ];

            $userMessage = $messages[$failureCode] ?? 'La tarjeta fue declinada por el banco emisor.';
        } elseif ($e instanceof ApiErrorException) {
            $failureCode = $e->getStripeCode() ?? 'api_error';
            $failureReason = "Error en API de Stripe: " . $e->getMessage();
            $userMessage = "Ocurrió un problema de comunicación con la pasarela de pagos.";
        }

        Log::warning('Fallo en transacción Stripe', [
            'failure_code' => $failureCode,
            'failure_reason' => $failureReason,
            'exception' => $e->getMessage(),
        ]);

        return [
            'failure_code' => $failureCode,
            'failure_reason' => $failureReason,
            'user_message' => $userMessage,
        ];
    }
}
