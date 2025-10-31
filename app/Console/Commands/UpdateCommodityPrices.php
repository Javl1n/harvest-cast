<?php

namespace App\Console\Commands;

use App\Models\Commodity;
use App\Models\Price;
use Illuminate\Console\Command;

class UpdateCommodityPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commodity:update-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all price records to realistic values based on market research';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating price records to realistic values...');
        $this->newLine();

        // Base prices per kg (PHP) - adjusted based on realistic income per acre research
        $basePrices = [
            'Rice' => 35,
            'Corn' => 20,
            'Ampalaya' => 65,
            'Eggplant' => 40,
            'Pechay' => 45,
            'Pechay Baguio' => 50,
            'Pole Sitao' => 60,
            'Squash' => 35,
            'Tomato' => 50,
            'Bell Pepper' => 180,
            'Broccoli' => 130,
            'Cauliflower' => 110,
            'Cabbage' => 30,
            'Carrots' => 60,
            'Celery' => 140,
            'Chayote' => 45,
            'Habichuelas/Baguio Beans' => 95,
            'Lettuce' => 130,
            'White Potato' => 55,
        ];

        $commodities = Commodity::with(['variants.prices'])->get();
        $updatedCount = 0;
        $totalPriceRecords = 0;

        foreach ($commodities as $commodity) {
            $basePrice = $basePrices[$commodity->name] ?? null;

            if (! $basePrice) {
                $this->warn("No price data for commodity: {$commodity->name}");

                continue;
            }

            $this->line("Processing {$commodity->name} (base price: ₱{$basePrice}/kg)...");

            foreach ($commodity->variants as $index => $variant) {
                // Add slight variation (±10%) for different variants of the same commodity
                $priceVariation = 1 + (($index % 3 - 1) * 0.1); // -10%, 0%, +10%
                $targetPrice = round($basePrice * $priceVariation, 2);

                // Get all price records for this variant
                $priceRecords = $variant->prices;

                if ($priceRecords->isEmpty()) {
                    $this->warn("  No price records for variant: {$variant->name}");

                    continue;
                }

                // Calculate average current price to determine adjustment ratio
                $avgCurrentPrice = $priceRecords->avg('price');
                $adjustmentRatio = $targetPrice / $avgCurrentPrice;

                // Update all price records proportionally
                foreach ($priceRecords as $priceRecord) {
                    $newPrice = round($priceRecord->price * $adjustmentRatio, 2);

                    $priceRecord->update([
                        'price' => $newPrice,
                    ]);

                    $totalPriceRecords++;
                }

                $this->line("  ✓ Updated {$variant->name}: {$priceRecords->count()} records (avg ₱{$avgCurrentPrice} → ₱{$targetPrice}/kg)");
                $updatedCount++;
            }

            $this->newLine();
        }

        $this->info("Successfully updated {$totalPriceRecords} price records across {$updatedCount} commodity variants.");

        return Command::SUCCESS;
    }
}
