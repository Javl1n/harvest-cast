<?php

namespace App\Jobs;

use App\Models\CropImage;
use App\Services\CropHealthAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeCropImage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CropImage $cropImage,
        public string $cropName
    ) {
        $this->onQueue('analysis');
    }

    /**
     * Execute the job.
     */
    public function handle(CropHealthAnalysisService $service): void
    {
        $analysis = $service->analyzeImage($this->cropImage->file_path, $this->cropName);

        $this->cropImage->update([
            'ai_analysis' => $analysis,
            'health_status' => $analysis['health_status'],
            'recommendations' => implode(' ', $analysis['recommendations']),
            'processed' => true,
        ]);
    }
}
