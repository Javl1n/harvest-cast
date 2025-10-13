<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\CommodityVariant;
use App\Models\Price;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting price import from CSV...');
        
        // Default CSV file path
        $csvFile = 'exports/prices_export.csv';
        
        // Check if file exists
        if (!Storage::disk('local')->exists($csvFile)) {
            $this->command->error("CSV file not found: storage/app/private/{$csvFile}");
            $this->command->info('Please export prices first using: php artisan export:prices-csv');
            return;
        }

        $csvPath = storage_path("app/private/{$csvFile}");
        $this->command->info("Reading CSV file: {$csvPath}");

        // Open and read CSV file
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->command->error('Could not open CSV file for reading.');
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);
        $this->command->info('CSV Headers: ' . implode(', ', $header));

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            try {
                // Extract data from CSV row
                list($date, $commodityName, $variantName, $price, $createdAt, $updatedAt) = $row;
                
                // Skip if essential data is missing
                if (empty($date) || empty($commodityName) || empty($variantName) || empty($price)) {
                    $skipped++;
                    continue;
                }

                // Find or create commodity
                $commodity = Commodity::firstOrCreate(['name' => trim($commodityName)]);

                // Find or create commodity variant
                $variant = CommodityVariant::firstOrCreate([
                    'commodity_id' => $commodity->id,
                    'name' => trim($variantName)
                ]);

                // Check if price already exists for this date and variant
                $existingPrice = Price::where([
                    'variant_id' => $variant->id,
                    'date' => $date
                ])->first();

                if ($existingPrice) {
                    // Update existing price
                    $existingPrice->update([
                        'price' => (float) $price,
                        'updated_at' => !empty($updatedAt) ? Carbon::parse($updatedAt) : now()
                    ]);
                    $this->command->info("Updated: {$commodityName} - {$variantName} for {$date}");
                } else {
                    // Create new price record
                    Price::create([
                        'variant_id' => $variant->id,
                        'price' => (float) $price,
                        'date' => $date,
                        'created_at' => !empty($createdAt) ? Carbon::parse($createdAt) : now(),
                        'updated_at' => !empty($updatedAt) ? Carbon::parse($updatedAt) : now()
                    ]);
                    $this->command->info("Created: {$commodityName} - {$variantName} for {$date}");
                }

                $imported++;

                // Progress indicator every 50 records
                if ($imported % 50 === 0) {
                    $this->command->line("Processed {$imported} records...");
                }

            } catch (\Exception $e) {
                $errors++;
                $this->command->error("Error processing row: " . $e->getMessage());
                $this->command->error("Row data: " . implode(', ', $row));
                
                // Stop if too many errors
                if ($errors > 10) {
                    $this->command->error('Too many errors. Stopping import.');
                    break;
                }
            }
        }

        fclose($handle);

        // Summary
        $this->command->info("\n📊 Import Summary:");
        $this->command->line("✅ Successfully imported: {$imported} records");
        $this->command->line("⏭️  Skipped (missing data): {$skipped} records");
        $this->command->line("❌ Errors: {$errors} records");
        
        if ($imported > 0) {
            $this->command->info("\n🎉 Price import completed successfully!");
        }
    }
}