<?php

namespace App\Console\Commands;

use App\Models\CropImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteOldCropImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crop-images:cleanup {--days=30 : Number of days to keep active images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old crop images to free up storage space';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysToKeep = (int) $this->option('days');
        $deleteDate = now()->subDays($daysToKeep);

        $this->info("Cleaning up crop images older than {$daysToKeep} days...");

        $imagesToDelete = CropImage::where('image_date', '<', $deleteDate)->get();

        $deletedCount = 0;
        foreach ($imagesToDelete as $image) {
            Storage::disk('public')->delete($image->file_path);
            $image->delete();
            $deletedCount++;
        }

        $this->info("Permanently deleted {$deletedCount} images and their files.");
        $this->info('Cleanup complete!');

        return Command::SUCCESS;
    }
}
