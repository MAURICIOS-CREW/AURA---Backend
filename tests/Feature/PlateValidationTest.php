<?php

namespace Tests\Feature;

use App\Events\AccessValidatedEvent;
use App\Jobs\SendAlertJob;
use App\Models\Residence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();
    }

    public function test_validates_registered_vehicle_access_successfully()
    {
        $residence = Residence::create([
            'block'  => 1,
            'number' => '101',
        ]);

        Vehicle::create([
            'residence_id' => $residence->id,
            'plate'        => 'ABC-123',
            'brand'        => 'Toyota',
            'color'        => 'Black',
        ]);

        // Send unnormalized plate input " abc 123 "
        $response = $this->postJson('/api/access/plate', [
            'plate'             => ' abc 123 ',
            'device_identifier' => 'CAM_01',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'granted',
                'message' => 'Acceso permitido',
                'data'    => [
                    'guest_name'   => 'Vehículo ABC-123',
                    'residence_id' => $residence->id,
                ],
            ]);

        $this->assertDatabaseHas('access_logs', [
            'scanned_code'      => 'abc 123',
            'status'            => 'granted',
            'access_type'       => 'plate',
            'method'            => 'scan',
            'device_identifier' => 'CAM_01',
        ]);

        Queue::assertPushed(SendAlertJob::class);
        Event::assertDispatched(AccessValidatedEvent::class);
    }

    public function test_denies_access_when_plate_is_not_found()
    {
        $response = $this->postJson('/api/access/plate', [
            'plate' => 'XYZ-999',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status'  => 'denied',
                'message' => 'Placa no encontrada',
                'data'    => null,
            ]);

        Queue::assertPushed(SendAlertJob::class);
        Event::assertDispatched(AccessValidatedEvent::class);
    }

    public function test_denies_access_when_vehicle_is_soft_deleted()
    {
        $residence = Residence::create([
            'block'  => 1,
            'number' => '102',
        ]);

        $vehicle = Vehicle::create([
            'residence_id' => $residence->id,
            'plate'        => 'DEF-456',
        ]);

        $vehicle->delete(); // Soft delete

        $response = $this->postJson('/api/access/plate', [
            'plate' => 'DEF-456',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => 'denied',
                'message' => 'Vehículo inhabilitado (eliminado)',
                'data'    => null,
            ]);

        Queue::assertPushed(SendAlertJob::class);
        Event::assertDispatched(AccessValidatedEvent::class);
    }
}
