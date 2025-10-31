<?php

use App\Jobs\AnalyzeCropImage;
use App\Models\CropImage;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

it('allows admin to upload crop image', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $schedule = Schedule::factory()->create();

    actingAs($admin)
        ->postJson('/crop-images', [
            'image' => UploadedFile::fake()->image('crop.jpg', 1024, 768),
            'schedule_id' => $schedule->id,
        ])
        ->assertCreated()
        ->assertJson([
            'message' => 'Image uploaded successfully. Analysis in progress.',
        ]);

    $this->assertDatabaseCount('crop_images', 1);

    $image = CropImage::first();
    expect($image->schedule_id)->toBe($schedule->id);
    expect($image->image_date->toDateString())->toBe(now()->toDateString());
    expect($image->processed)->toBeFalse();

    Queue::assertPushed(AnalyzeCropImage::class);
});

it('prevents farmer from uploading images', function () {
    $farmer = User::factory()->create(['role' => 'farmer']);
    $schedule = Schedule::factory()->create();

    actingAs($farmer)
        ->postJson('/crop-images', [
            'image' => UploadedFile::fake()->image('crop.jpg'),
            'schedule_id' => $schedule->id,
        ])
        ->assertForbidden();
});

it('prevents duplicate images on same day', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $schedule = Schedule::factory()->create();

    CropImage::factory()->create([
        'schedule_id' => $schedule->id,
        'image_date' => now()->toDateString(),
    ]);

    actingAs($admin)
        ->postJson('/crop-images', [
            'image' => UploadedFile::fake()->image('crop.jpg'),
            'schedule_id' => $schedule->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image file type', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $schedule = Schedule::factory()->create();

    actingAs($admin)
        ->postJson('/crop-images', [
            'image' => UploadedFile::fake()->create('document.pdf', 100),
            'schedule_id' => $schedule->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image file size', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $schedule = Schedule::factory()->create();

    actingAs($admin)
        ->postJson('/crop-images', [
            'image' => UploadedFile::fake()->image('crop.jpg')->size(11000), // 11MB
            'schedule_id' => $schedule->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('allows admin to delete crop image', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cropImage = CropImage::factory()->create();

    actingAs($admin)
        ->deleteJson("/crop-images/{$cropImage->id}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('crop_images', [
        'id' => $cropImage->id,
    ]);
});

it('prevents farmer from deleting images', function () {
    $farmer = User::factory()->create(['role' => 'farmer']);
    $cropImage = CropImage::factory()->create();

    actingAs($farmer)
        ->deleteJson("/crop-images/{$cropImage->id}")
        ->assertForbidden();
});

it('logs analysis errors for debugging', function () {
    // Don't use Storage::fake() - use real storage for this test
    $admin = User::factory()->create(['role' => 'admin']);
    $schedule = Schedule::factory()->create();

    // Create a real image file in temporary storage
    $tempImage = tmpfile();
    $tempPath = stream_get_meta_data($tempImage)['uri'];

    // Create a simple 1x1 PNG image
    $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    file_put_contents($tempPath, $imageData);

    $image = new UploadedFile($tempPath, 'test-crop.png', 'image/png', null, true);

    // Upload the image (this will use real storage)
    actingAs($admin)
        ->postJson('/crop-images', [
            'image' => $image,
            'schedule_id' => $schedule->id,
        ])
        ->assertCreated();

    $cropImage = CropImage::first();
    expect($cropImage)->not()->toBeNull();
    expect($cropImage->processed)->toBeFalse();

    // Check if the file actually exists
    $fullPath = Storage::disk('public')->path($cropImage->file_path);
    dump([
        'file_path_in_db' => $cropImage->file_path,
        'full_path' => $fullPath,
        'file_exists' => file_exists($fullPath),
        'storage_url' => Storage::disk('public')->url($cropImage->file_path),
    ]);

    // Now run the analysis job
    $job = new AnalyzeCropImage($cropImage, $schedule->commodity->name);

    try {
        $job->handle(app(\App\Services\CropHealthAnalysisService::class));
        $cropImage->refresh();

        dump([
            'after_analysis' => [
                'processed' => $cropImage->processed,
                'health_status' => $cropImage->health_status,
                'ai_analysis' => $cropImage->ai_analysis,
                'recommendations' => $cropImage->recommendations,
            ],
        ]);

    } catch (\Exception $e) {
        dump([
            'exception' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        ]);
    } finally {
        // Clean up - delete the uploaded file
        Storage::disk('public')->delete($cropImage->file_path);
        $cropImage->forceDelete();
    }

    expect(true)->toBeTrue(); // Always pass, this is for debugging
})->skip('Manual debugging test - requires OpenAI API key');
