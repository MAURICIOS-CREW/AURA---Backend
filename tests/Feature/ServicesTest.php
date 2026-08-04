<?php

namespace Tests\Feature;

use App\Models\AccessCode;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Residence;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $mobileUser;
    protected Residence $residence;
    protected string $adminToken;
    protected string $mobileToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrador']);
        $residentRole = Role::create(['name' => 'resident', 'display_name' => 'Residente']);

        // Crear usuarios
        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->mobileUser = User::create([
            'name' => 'Resident Test',
            'username' => 'resident_test_' . uniqid(),
            'email' => 'resident_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role_id' => $residentRole->id,
        ]);

        $this->adminUser->load('role');
        $this->mobileUser->load('role');

        // Crear residencia y asociar al usuario móvil
        $this->residence = Residence::create([
            'block' => 1,
            'number' => '101',
        ]);
        $this->mobileUser->residences()->attach($this->residence->id, ['is_primary_owner' => true]);

        // Generar tokens Sanctum
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;
        $this->mobileToken = $this->mobileUser->createToken('mobile-token')->plainTextToken;
    }

    public function test_admin_can_create_service_with_images(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('service1.jpg');
        $file2 = UploadedFile::fake()->image('service2.png');

        Sanctum::actingAs($this->adminUser, ['*'], 'api');
        $response = $this->postJson('/api/admin/services', [
            'title' => 'Jardinería Profesional',
            'description' => 'Corte de pasto y arreglo de plantas',
            'price' => 250.00,
            'is_active' => true,
            'images' => [$file1, $file2],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Jardinería Profesional')
            ->assertJsonPath('data.price', '250.00');

        $serviceId = $response->json('data.id');
        $service = Service::find($serviceId);
        $this->assertCount(2, $service->images);
    }

    public function test_admin_can_upload_and_delete_service_images_separately(): void
    {
        Storage::fake('public');

        $service = Service::create([
            'title' => 'Corte de Pasto',
            'price' => 150.00,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->adminUser, ['*'], 'api');

        // 1. Subir imágenes
        $file = UploadedFile::fake()->image('nueva_foto.jpg');
        $uploadResp = $this->postJson("/api/admin/services/{$service->id}/images", [
            'images' => [$file],
        ]);

        $uploadResp->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $uploadedPath = $uploadResp->json('data.images.0');
        $this->assertNotNull($uploadedPath);

        // 2. Eliminar imagen
        $deleteResp = $this->deleteJson("/api/admin/services/{$service->id}/images", [
            'image_path' => $uploadedPath,
        ]);

        $deleteResp->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(0, 'data.images');
    }

    public function test_mobile_user_can_view_active_services(): void
    {
        Service::create([
            'title' => 'Limpieza de Cisterna',
            'description' => 'Lavado profundo',
            'price' => 500.00,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'Servicio Inactivo',
            'description' => 'No debe aparecer',
            'price' => 100.00,
            'is_active' => false,
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');
        $response = $this->getJson('/api/mobile/services');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Limpieza de Cisterna');
    }

    public function test_mobile_user_can_contract_service_and_creates_financial_charge(): void
    {
        $service = Service::create([
            'title' => 'Mantenimiento de Aire Acondicionado',
            'description' => 'Servicio técnico de A/C',
            'price' => 450.00,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');
        $response = $this->postJson("/api/mobile/services/{$service->id}/contract", [
            'residence_id' => $this->residence->id,
            'preferred_date' => now()->addDays(2)->format('Y-m-d'),
            'visit_time_from' => '10:00',
            'visit_time_to' => '14:00',
            'notes' => 'Tocar timbre de la casa',
            'payment_method' => 'stripe',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'created')
            ->assertJsonPath('data.amount', '450.00');

        $contractedId = $response->json('data.id');
        $contract = ContractedService::find($contractedId);

        $this->assertNotNull($contract);
        $this->assertEquals('created', $contract->status);
        $this->assertNotNull($contract->charge_id);

        $charge = FinancialCharge::find($contract->charge_id);
        $this->assertEquals('paid', $charge->status);
        $this->assertEquals(450.00, $charge->amount);
    }

    public function test_admin_can_schedule_contracted_service_and_generates_access_code_qr(): void
    {
        $service = Service::create([
            'title' => 'Pintura Fachada',
            'price' => 1200.00,
            'is_active' => true,
        ]);

        $charge = FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 1200.00,
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'paid',
        ]);

        $contract = ContractedService::create([
            'service_id' => $service->id,
            'user_id' => $this->mobileUser->id,
            'residence_id' => $this->residence->id,
            'charge_id' => $charge->id,
            'preferred_date' => now()->addDays(3)->format('Y-m-d'),
            'visit_time_from' => '09:00',
            'visit_time_to' => '12:00',
            'amount' => 1200.00,
            'status' => 'created',
        ]);

        $scheduledAt = now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s');

        Sanctum::actingAs($this->adminUser, ['*'], 'api');
        $response = $this->postJson("/api/admin/contracted-services/{$contract->id}/schedule", [
            'exact_scheduled_at' => $scheduledAt,
            'notes' => 'Personal asignado: Juan Pérez',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.access_code.type', 'service');

        $this->assertDatabaseHas('access_codes', [
            'contracted_service_id' => $contract->id,
            'type' => 'service',
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_refund_contracted_service_and_updates_financial_charge(): void
    {
        $service = Service::create([
            'title' => 'Plomería Urgencia',
            'price' => 300.00,
            'is_active' => true,
        ]);

        $charge = FinancialCharge::create([
            'residence_id' => $this->residence->id,
            'amount' => 300.00,
            'month' => now()->month,
            'year' => now()->year,
            'status' => 'paid',
        ]);

        $contract = ContractedService::create([
            'service_id' => $service->id,
            'user_id' => $this->mobileUser->id,
            'residence_id' => $this->residence->id,
            'charge_id' => $charge->id,
            'preferred_date' => now()->addDay()->format('Y-m-d'),
            'visit_time_from' => '08:00',
            'visit_time_to' => '10:00',
            'amount' => 300.00,
            'status' => 'created',
        ]);

        Sanctum::actingAs($this->adminUser, ['*'], 'api');
        $response = $this->patchJson("/api/admin/contracted-services/{$contract->id}/status", [
            'status' => 'refunded',
            'notes' => 'Cancelado a petición del usuario, reembolso procesado',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'refunded');

        $this->assertEquals('refunded', $charge->fresh()->status);
    }

    public function test_mobile_user_can_mark_contracted_service_as_completed(): void
    {
        $service = Service::create([
            'title' => 'Limpieza de Alberca',
            'price' => 350.00,
            'is_active' => true,
        ]);

        $contract = ContractedService::create([
            'service_id' => $service->id,
            'user_id' => $this->mobileUser->id,
            'residence_id' => $this->residence->id,
            'preferred_date' => now()->format('Y-m-d'),
            'visit_time_from' => '12:00',
            'visit_time_to' => '15:00',
            'amount' => 350.00,
            'status' => 'in_progress',
        ]);

        Sanctum::actingAs($this->mobileUser, ['*'], 'api');
        $response = $this->patchJson("/api/mobile/contracted-services/{$contract->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'completed');
    }
}
