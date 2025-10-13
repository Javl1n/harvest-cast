<?php

namespace App\Console\Commands;

use App\Models\Price;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportPricesToCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:prices-csv {--file=prices_export.csv} {--date-from=} {--date-to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export commodity prices to CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->option('file');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        $this->info('Starting price export...');

        // Build query
        $query = Price::with(['variant.commodity']);

        // Apply date filters if provided
        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
            $this->info("Filtering from date: {$dateFrom}");
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
            $this->info("Filtering to date: {$dateTo}");
        }

        // Order by date and commodity
        $query->orderBy('date', 'desc')
              ->orderBy('created_at', 'desc');

        $prices = $query->get();

        if ($prices->isEmpty()) {
            $this->warn('No prices found to export.');
            return 0;
        }

        $this->info("Found {$prices->count()} price records to export.");

        // Create CSV content
        $csvData = [];
        
        // CSV Headers
        $csvData[] = [
            'Date',
            'Commodity',
            'Variant',
            'Price (PHP)',
            'Created At',
            'Updated At'
        ];

        // Add data rows
        foreach ($prices as $price) {
            $csvData[] = [
                $price->date,
                $price->variant->commodity->name ?? 'Unknown Commodity',
                $price->variant->name ?? 'Unknown Variant',
                $price->price,
                $price->created_at->format('Y-m-d H:i:s'),
                $price->updated_at->format('Y-m-d H:i:s')
            ];
        }

        // Create CSV file path
        $filePath = "exports/{$filename}";
        
        // Ensure exports directory exists
        Storage::disk('local')->makeDirectory('exports');

        // Generate CSV content
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        // Save to storage
        Storage::disk('local')->put($filePath, $csvContent);
        
        $fullPath = storage_path("app/{$filePath}");
        
        $this->info("✅ Successfully exported {$prices->count()} records to: {$fullPath}");
        $this->line("You can also find it at: storage/app/{$filePath}");

        // Display summary statistics
        $this->displaySummary($prices);

        return 0;
    }

    private function displaySummary($prices)
    {
        $this->info("\n📊 Export Summary:");
        
        // Date range
        $dates = $prices->pluck('date')->unique()->sort();
        $this->line("Date range: {$dates->first()} to {$dates->last()}");
        
        // Commodity count
        $commodities = $prices->groupBy(function($price) {
            return $price->variant->commodity->name ?? 'Unknown';
        });
        $this->line("Commodities: {$commodities->count()} unique commodities");
        
        // Top commodities by record count
        $topCommodities = $commodities->map(function($group) {
            return $group->count();
        })->sortDesc()->take(5);
        
        $this->line("\nTop commodities by record count:");
        foreach ($topCommodities as $commodity => $count) {
            $this->line("  • {$commodity}: {$count} records");
        }
        
        // Price range
        $prices_values = $prices->pluck('price')->filter();
        if ($prices_values->isNotEmpty()) {
            $this->line("\nPrice range: PHP {$prices_values->min()} - PHP {$prices_values->max()}");
            $this->line("Average price: PHP " . number_format($prices_values->avg(), 2));
        }
    }
}