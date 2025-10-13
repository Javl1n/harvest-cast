<?php

namespace App\Http\Controllers;

use App\Jobs\NormalizeCommodities;
use App\Models\Commodity;
use App\Models\Price;
use Illuminate\Http\Request;

class CropPricesController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'date' => 'nullable|date|date_format:Y-m-d',
            'force' => 'nullable|boolean'
        ]);

        // Use provided date or default to today
        $date = $request->date ?? now()->format('Y-m-d');

        // Check if there are already price records for this date (unless forced)
        $force = $request->boolean('force', false);
        
        if (!$force) {
            $existingPricesCount = Price::whereDate('date', $date)->count();
            
            if ($existingPricesCount > 0) {
                return response()->json([
                    'message' => 'Price records already exist for this date. Use "force: true" to override.',
                    'date' => $date,
                    'existing_records_count' => $existingPricesCount,
                    'skipped' => true
                ], 200);
            }
        }

        $records = collect($request->data)->filter(function (array $value, int $key) {

            $excludedCategories = [
                'Fish Products', 
                'Beef Meat Products', 
                'Pork Meat Products', 
                'Products', 
                'Poultry Products',
                // 'Lowland Vegetables',
                // 'Highland Vegetables',
                'Spices', 
                'Fruits', 
                'Other Basic Commodities'
            ];

            return ($value["category"] && !in_array($value['category'], $excludedCategories));

        })->values()->toArray();

        // dump($records);

        $prompt = <<<PROMPT
        ### RAW INPUT DATA
        PROMPT;
        
        $prompt .= json_encode($records, JSON_PRETTY_PRINT);

        // dump($prompt);

        NormalizeCommodities::dispatch($prompt, $date);

        return response()->json([
            'message' => 'Normalization Started',
            'date' => $date,
            'records_count' => count($records),
            'forced' => $force
        ]);
    }
}
