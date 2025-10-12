<?php

namespace App\Jobs;

use App\Models\Commodity;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

class NormalizeCommodities implements ShouldQueue
{
    use Queueable;


    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $prompt
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Prism::text()
        ->using(Provider::OpenAI, 'gpt-5-mini')
        ->withSystemPrompt($this->buildSystemPrompt())
        ->withPrompt($this->prompt)
        ->withMaxTokens(12000)
        ->withClientOptions([
            'timeout' => 300,
            "connect_timeout" => 10,
        ])
        ->asText();

        $array = json_decode($response->text, true);
        
        SavePrices::dispatch($array);
    }

    private function buildSystemPrompt()
    { 
        $commodities = Commodity::with('variants')->get();

        $standardCommodities = $commodities->mapWithKeys(function ($commodity) {
            return [
                $commodity->name => $commodity->variants->pluck('name')->toArray(),
            ];
        })->toArray();

            
        $prompt = <<<PROMPT
        You are a data normalizer for local agricultural commodity prices.

        ### TASK
        Clean and standardize the given list of commodity price records.

        ### RULES
        1. Ignore records that have a null, empty, or missing commodity name or price.
        2. Ignore records marked as imported (any containing "Imported" or "Foreign").
        3. Normalize commodity names based on this official list:
        PROMPT;

        $prompt .= json_encode(array_keys($standardCommodities), JSON_PRETTY_PRINT);

        $prompt .= <<<PROMPT
        4. Normalize variants using the mappings below:
        PROMPT;

        $prompt .= json_encode($standardCommodities, JSON_PRETTY_PRINT);

        $prompt .= <<<PROMPT
        5. If a record's commodity name includes additional descriptive keywords not found among existing variants (e.g., “Pechay Tagalog” when only “Pechay Baguio” exists), create a new variant under the same commodity using those keywords. If the record's name has no extra descriptive keywords, use the base commodity name as its variant (e.g., “Pechay” → variant = “Pechay”).
        6. Only return valid local data.
        7. Keep the output format strictly as a JSON array.
        8. Ignore these categories: Spices, Other Commodities, Beef Meat Products, Fish Products, Pork Meat Products, Other Livestock Products, Poultry Products.

        ### OUTPUT FORMAT
        [
            {
                "commodity": "Pechay",
                "variant": "Native Pechay",
                "price": 45
            }
        ]
        PROMPT;

        return $prompt;
    }
}
