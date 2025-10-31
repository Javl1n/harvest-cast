<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCropImageRequest;
use App\Jobs\AnalyzeCropImage;
use App\Models\CropImage;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CropImageController extends Controller
{
    public function store(StoreCropImageRequest $request): JsonResponse
    {
        $schedule = Schedule::with('commodity')->findOrFail($request->schedule_id);

        $file = $request->file('image');
        $date = now()->format('Y-m-d');
        $timestamp = now()->timestamp;
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$timestamp}-{$originalName}";

        $path = $file->storeAs(
            "crop-images/{$schedule->id}/{$date}",
            $filename,
            'public'
        );

        $cropImage = CropImage::create([
            'schedule_id' => $schedule->id,
            'file_path' => $path,
            'file_name' => $filename,
            'image_date' => now()->toDateString(),
            'processed' => false,
        ]);

        AnalyzeCropImage::dispatch($cropImage, $schedule->commodity->name)->onQueue('analysis');

        return response()->json([
            'message' => 'Image uploaded successfully. Analysis in progress.',
            'image' => [
                'id' => $cropImage->id,
                'image_url' => $cropImage->image_url,
                'image_date' => $cropImage->image_date->toDateString(),
                'processed' => $cropImage->processed,
            ],
        ], 201);
    }

    public function show(CropImage $image): JsonResponse
    {
        return response()->json([
            'id' => $image->id,
            'schedule_id' => $image->schedule_id,
            'image_url' => $image->image_url,
            'file_name' => $image->file_name,
            'ai_analysis' => $image->ai_analysis,
            'health_status' => $image->health_status,
            'recommendations' => $image->recommendations,
            'processed' => $image->processed,
            'image_date' => $image->image_date->toDateString(),
            'created_at' => $image->created_at->toIso8601String(),
        ]);
    }

    public function destroy(CropImage $image): JsonResponse
    {
        $this->authorize('delete', $image);

        Storage::disk('public')->delete($image->file_path);

        $image->forceDelete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }
}
