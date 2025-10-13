<?php

namespace App\Jobs;

use App\Models\Commodity;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SavePrices implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $commodities,
        public string $date
    )
    {
        $this->onQueue('saving');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->commodities as $item) {
            // Skip if essential data is missing
            if (empty($item['commodity']) || empty($item['variant']) || !isset($item['price'])) {
                continue;
            }

            // Find the commodity (skip if it doesn't exist)
            $commodity = Commodity::where('name', trim($item['commodity']))->first();
            if (!$commodity) {
                continue; // Skip this item if commodity doesn't exist
            }

            // Find the variant (skip if it doesn't exist)
            $variant = $commodity->variants()->where('name', trim($item['variant']))->first();
            if (!$variant) {
                continue; // Skip this item if variant doesn't exist
            }

            // Update or create the price for the existing variant
            $variant->prices()->updateOrCreate([
                'date' => $this->date,
            ], [
                'price' => $item['price'],
            ]);
        }
    }
}
