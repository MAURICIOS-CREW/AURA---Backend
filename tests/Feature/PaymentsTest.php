<?php

namespace Tests\Feature;

use App\Models\AccessCode;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $mobileUser;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        $residentRole = Role::create(['name' => 'resident', 'display_name' => 'Residente']);

        $this->mobileUser = User::create([
            'name' => 'Residente Pagos',
            'username' => 'residente_pagos_' . uniqid(),
            'email' => 'pagos_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => $residentRole->id,
        ]);
        $this->mobileUser->load('role');

        $this->residence = Residence::create([
            'block' => 2,
            'number' => '202',
        ]);
        $this->mobileUser->residences()->attach($this->residence->id, ['is_primary_owner' => true]);
    }

    public function test_mobile_user_can_contract_recurrent_service_and_generates_reusable_access_code_qr(): void
    {
        $service = Service::create([
            'title' => 'Mantenimiento de Jardín Recurrente',
            'description' => 'Servicio quincenal',
            'price' => 350.00,
            'is_active' => true,
            'is_recurrent' => false,
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');

        $response = $this->postJson("/api/mobile/services/{$service->id}/contract", [
            'residence_id' => $this->residence->id,
            'preferred_date' => now()->addDay()->format('Y-m-d'),
            'visit_time_from' => '09:00',
            'visit_time_to' => '11:00',
            'is_recurrent' => true,
            'suggested_schedule' => ['Lunes', 'Miércoles'],
            'notes' => 'Horario matutino preferido',
            'payment_method' => 'stripe',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_recurrent', true)
            ->assertJsonPath('data.suggested_schedule.0', 'Lunes');

        $contractedId = $response->json('data.id');
        $this->assertDatabaseHas('contracted_services', [
            'id' => $contractedId,
            'is_recurrent' => 1,
        ]);

        $accessCode = AccessCode::where('contracted_service_id', $contractedId)->first();
        $this->assertNotNull($accessCode);
        $this->assertEquals('service', $accessCode->type);
        $this->assertNull($accessCode->max_uses);
        $this->assertEquals(['Lunes', 'Miércoles'], $accessCode->active_days);
    }

    public function test_mobile_user_can_get_payments_summary_and_pending_balance(): void
    {
        // 1. Servicio obligatorio mensual
        Service::create([
            'title' => 'Cuota Mensual Mantenimiento',
            'price' => 1200.00,
            'is_active' => true,
            'is_recurrent' => true,
        ]);

        // 2. Cargo financiero pendiente del mes pasado
        FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 1200.00,
            'month' => now()->subMonth()->month,
            'year' => now()->subMonth()->year,
            'status' => 'pending',
        ]);

        // 3. Cargo pagado en histórico
        $paidCharge = FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 1200.00,
            'month' => now()->subMonths(2)->month,
            'year' => now()->subMonths(2)->year,
            'status' => 'paid',
        ]);

        Payment::create([
            'charge_id' => $paidCharge->id,
            'user_id' => $this->mobileUser->id,
            'amount' => 1200.00,
            'payment_method' => 'transfer',
            'status' => 'approved',
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');

        $response = $this->getJson('/api/mobile/payments');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    'saldo_pendiente',
                    'pagos_pendientes',
                    'historico_pagos' => [
                        'current_page',
                        'data',
                        'per_page',
                        'total',
                    ],
                ],
            ]);

        $saldo = (float) $response->json('data.saldo_pendiente');
        $this->assertGreaterThanOrEqual(1400.00, $saldo);
        $this->assertEquals(10, $response->json('data.historico_pagos.per_page'));
    }

    public function test_mobile_user_can_process_payment_of_selected_debts(): void
    {
        $pendingCharge = FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 1500.00,
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');

        $response = $this->postJson('/api/mobile/payments/pay', [
            'items' => [
                [
                    'type' => 'financial_charge',
                    'id' => $pendingCharge->id,
                ],
            ],
            'payment_method' => 'stripe',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_paid', '1500.00');

        $this->assertEquals('paid', $pendingCharge->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'charge_id' => $pendingCharge->id,
            'user_id' => $this->mobileUser->id,
            'status' => 'approved',
        ]);
    }

    public function test_mobile_user_can_create_stripe_payment_intent(): void
    {
        $pendingCharge = FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 500.00,
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');

        // Mock StripeService
        $mockStripe = $this->createMock(\App\Services\StripeService::class);
        $dummyIntent = \Stripe\PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'client_secret' => 'pi_test_123_secret_xyz',
        ]);

        $mockStripe->method('createPaymentIntent')->willReturn($dummyIntent);
        $mockStripe->method('getPublishableKey')->willReturn('pk_test_mock_key');
        $this->app->instance(\App\Services\StripeService::class, $mockStripe);

        $response = $this->postJson('/api/mobile/payments/stripe/create-intent', [
            'items' => [
                [
                    'type' => 'financial_charge',
                    'id' => $pendingCharge->id,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.payment_intent_id', 'pi_test_123')
            ->assertJsonPath('data.client_secret', 'pi_test_123_secret_xyz')
            ->assertJsonPath('data.publishable_key', 'pk_test_mock_key');
    }

    public function test_mobile_user_handles_stripe_payment_rejection(): void
    {
        $pendingCharge = FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 800.00,
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');

        // Mock StripeService returning Exception
        $mockStripe = $this->createMock(\App\Services\StripeService::class);
        $exception = new \Exception('Your card has insufficient funds.');

        $mockStripe->method('createAndConfirmPaymentIntent')->willThrowException($exception);
        $mockStripe->method('parseStripeException')->willReturn([
            'failure_code' => 'insufficient_funds',
            'failure_reason' => 'Tarjeta rechazada por Stripe: Your card has insufficient funds.',
            'user_message' => 'La tarjeta no cuenta con fondos suficientes.',
        ]);
        $this->app->instance(\App\Services\StripeService::class, $mockStripe);

        $response = $this->postJson('/api/mobile/payments/pay', [
            'items' => [
                [
                    'type' => 'financial_charge',
                    'id' => $pendingCharge->id,
                ],
            ],
            'payment_method' => 'stripe',
            'payment_method_id' => 'pm_card_chargeDeclinedInsufficientFunds',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('decline_code', 'insufficient_funds');

        // El cargo debe permanecer pendiente
        $this->assertEquals('pending', $pendingCharge->fresh()->status);

        // Se debe haber creado el registro de pago rechazado (refused)
        $this->assertDatabaseHas('payments', [
            'charge_id' => $pendingCharge->id,
            'user_id' => $this->mobileUser->id,
            'status' => 'refused',
            'failure_code' => 'insufficient_funds',
        ]);
    }
}
