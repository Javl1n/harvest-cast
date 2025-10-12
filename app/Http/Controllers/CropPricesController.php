<?php

namespace App\Http\Controllers;

use App\Jobs\NormalizeCommodities;
use App\Models\Commodity;
use Illuminate\Http\Request;

class CropPricesController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['data' => 'required|array']);

        $records = collect($request->data)->filter(function (array $value, int $key) {

            $excludedCategories = [
                'Fish Products', 
                'Beef Meat Products', 
                'Pork Meat Products', 
                'Products', 
                'Poultry Products',
                'Lowland Vegetables',
                'Highland Vegetables',
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

        NormalizeCommodities::dispatch($prompt);

        return response()->json(['message' => 'Normalization Started']);
    }
}
