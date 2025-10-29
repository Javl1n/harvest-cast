<?php

use App\Models\Schedule;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-Based Authorization', function () {
    describe('Admin Role', function () {
        it('allows admin to access create planting page', function () {
            $admin = User::factory()->admin()->create();
            $sensor = Sensor::factory()->create();

            $response = $this->actingAs($admin)->get("/crops/create/sensor/{$sensor->id}");

            $response->assertSuccessful();
        });

        it('allows admin to create plantings', function () {
            $admin = User::factory()->admin()->create();
            $sensor = Sensor::factory()->create();

            $response = $this->actingAs($admin)->post('/crops', [
                'commodity_id' => 1,
                'sensor_id' => $sensor->id,
                'hectares' => 10.5,
                'seeds_planted' => 1000,
                'date_planted' => now()->toDateString(),
                'expected_harvest_date' => now()->addMonths(3)->toDateString(),
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('schedules', [
                'sensor_id' => $sensor->id,
                'hectares' => 10.5,
            ]);
        });

        it('allows admin to mark crops as harvested', function () {
            $admin = User::factory()->admin()->create();
            $schedule = Schedule::factory()->create([
                'actual_harvest_date' => null,
            ]);

            $response = $this->actingAs($admin)->patch("/crops/harvest/{$schedule->id}");

            $response->assertRedirect();
            $schedule->refresh();
            expect($schedule->actual_harvest_date)->not->toBeNull();
        });

        it('allows admin to view all sensors', function () {
            $admin = User::factory()->admin()->create();
            $sensor = Sensor::factory()->create();

            $response = $this->actingAs($admin)->get("/calendar/sensor/{$sensor->id}");

            $response->assertSuccessful();
        });
    });

    describe('Farmer Role', function () {
        it('denies farmer access to create planting page', function () {
            $farmer = User::factory()->create();
            $sensor = Sensor::factory()->create();

            $response = $this->actingAs($farmer)->get("/crops/create/sensor/{$sensor->id}");

            $response->assertForbidden();
        });

        it('denies farmer ability to create plantings', function () {
            $farmer = User::factory()->create();
            $sensor = Sensor::factory()->create();

            $response = $this->actingAs($farmer)->post('/crops', [
                'commodity_id' => 1,
                'sensor_id' => $sensor->id,
                'hectares' => 10.5,
                'seeds_planted' => 1000,
                'date_planted' => now()->toDateString(),
                'expected_harvest_date' => now()->addMonths(3)->toDateString(),
            ]);

            $response->assertForbidden();
            $this->assertDatabaseMissing('schedules', [
                'sensor_id' => $sensor->id,
            ]);
        });

        it('denies farmer ability to mark crops as harvested', function () {
            $farmer = User::factory()->create();
            $schedule = Schedule::factory()->create([
                'actual_harvest_date' => null,
            ]);

            $response = $this->actingAs($farmer)->patch("/crops/harvest/{$schedule->id}");

            $response->assertForbidden();
            $schedule->refresh();
            expect($schedule->actual_harvest_date)->toBeNull();
        });

        it('allows farmer to view all sensors', function () {
            $farmer = User::factory()->create();
            $sensor = Sensor::factory()->create();

            $response = $this->actingAs($farmer)->get("/calendar/sensor/{$sensor->id}");

            $response->assertSuccessful();
        });

        it('allows farmer to view sensor calendar', function () {
            $farmer = User::factory()->create();

            $response = $this->actingAs($farmer)->get('/calendar');

            $response->assertSuccessful();
        });
    });

    describe('Guest Users', function () {
        it('redirects guests to login when accessing protected routes', function () {
            $sensor = Sensor::factory()->create();

            $response = $this->get("/crops/create/sensor/{$sensor->id}");

            $response->assertRedirect('/login');
        });
    });
});
