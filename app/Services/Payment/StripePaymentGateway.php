<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentGateway
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret', 'sk_test_123'));
    }

    public function createPaymentIntent(float $amount, string $currency, array $metadata): PaymentIntent
    {
        $amountInCents = intval(round($amount * 100));

        return PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret)
    {
        try {
            return Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException $e) {
            throw new \Exception('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            throw new \Exception('Invalid signature', 400);
        }
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }
}
