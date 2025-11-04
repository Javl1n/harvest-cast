<?php

use App\Models\Commodity;
use App\Models\Sensor;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('can access home page', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
});

it('redirects to login when accessing dashboard unauthenticated', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('redirects dashboard to calendar index when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('calendar.index'));
});

it('can access calendar index when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/calendar');

    $response->assertSuccessful();
});

it('can access calendar show with sensor when authenticated', function () {
    $user = User::factory()->create();
    $sensor = Sensor::factory()->create();

    actingAs($user);

    $response = $this->get("/calendar/sensor/{$sensor->id}");

    $response->assertSuccessful();
});

it('requires admin role to access crop create page', function () {
    $user = User::factory()->create();
    $sensor = Sensor::factory()->create();

    actingAs($user);

    $response = $this->get("/crops/create/sensor/{$sensor->id}");

    $response->assertForbidden();
});

it('can access crop create page as admin', function () {
    $admin = User::factory()->admin()->create();
    $sensor = Sensor::factory()->create();

    actingAs($admin);

    $response = $this->get("/crops/create/sensor/{$sensor->id}");

    $response->assertSuccessful();
});

it('can access pricing forecast index when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/pricing-forecast');

    $response->assertSuccessful();
});

it('can access pricing forecast show with valid commodity when authenticated', function () {
    $user = User::factory()->create();
    $commodity = Commodity::create(['name' => 'Test Commodity']);

    actingAs($user);

    $response = $this->get("/pricing-forecast/{$commodity->id}");

    $response->assertSuccessful();
});

it('returns 404 for invalid commodity in pricing forecast', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/pricing-forecast/invalid-commodity-id');

    $response->assertNotFound();
});

it('can access settings appearance when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/settings/appearance');

    $response->assertSuccessful();
});

it('can access settings profile when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/settings/profile');

    $response->assertSuccessful();
});

it('can access settings password when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/settings/password');

    $response->assertSuccessful();
});

it('can access login page', function () {
    $response = $this->get('/login');

    $response->assertSuccessful();
});

it('can access register page', function () {
    $response = $this->get('/register');

    $response->assertSuccessful();
});

it('can access forgot password page', function () {
    $response = $this->get('/forgot-password');

    $response->assertSuccessful();
});

it('redirects settings to profile when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = $this->get('/settings');

    $response->assertRedirect('/settings/profile');
});

it('redirects unauthenticated users from protected routes to login', function (string $route) {
    $response = $this->get($route);

    $response->assertRedirect('/login');
})->with([
    '/calendar',
    '/pricing-forecast',
    '/settings/profile',
    '/settings/password',
    '/settings/appearance',
]);
