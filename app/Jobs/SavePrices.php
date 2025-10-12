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
        public array $commodities
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->commodities as $item) {
            $commodity = Commodity::where('name', $item['commodity'])->first();

            $commodity->variants()->firstOrCreate([
                'name' => $item['variant']
            ])->prices()->updateOrCreate([
                'date' => now('Asia/Manila')->format("Y-m-d"),
            ],[
                "price" => $item['price'],
            ]);
        }
    }
}
