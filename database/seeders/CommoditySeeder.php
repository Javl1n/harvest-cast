<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommoditySeeder extends Seeder
{
    /**
     * Define which commodity types to include in the system.
     * Comment out or remove commodities you don't want to seed.
     */
    protected function getAllowedCommodities(): array
    {
        return [
            // 'Rice',
            // 'Corn',
            // 'Ampalaya',
            // 'Eggplant',
            // 'Pechay',
            // 'Pechay Baguio',
            'Pole Sitao',
            // 'Squash',
            // 'Tomato',
            // 'Bell Pepper',
            // 'Broccoli',
            // 'Cauliflower',
            // 'Cabbage',
            // 'Carrots',
            // 'Celery',
            // 'Chayote',
            // 'Habichuelas/Baguio Beans',
            // 'Lettuce',
            // 'White Potato',
            // Spices
            'Chili',
            // 'Ginger',
            // 'Garlic',
            // 'Red Onion',
            // 'White Onion',
            // 'Spring Onion',
            // Highland Vegetables
            'Sweet Potato',
            // 'Radish',
            // 'Leeks',
            // 'Mustard Greens',
            // 'Snap Peas',
            // 'Snow Peas',
            // 'Turnip',
            // Add more commodities here as needed
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commodities = [
            "Rice" => [
                "Special Rice White Rice",
                "Premium, 5% broken",
                "Well Milled, 1-19% bran streak",
                "Regular Milled, 20-40% bran streak"
            ],
            "Corn" => [
                "White Cob, Glutinous",
                "Grits White, Food Grade",
                "Grits Yellow, Food Grade",
                "Cracked Yellow, Food Grade",
                "Grits, Feed Grade"
            ],
            "Ampalaya" => [
                // "Ampalaya",
                "4-5 pcs/kg"
            ],
            "Eggplant" => [
                // "Eggplant",
                "3-4 Small Bundles"
            ],
            "Pechay" => [
                "3-4 Small Bundles",
            ],
            "Pechay Baguio" => [
                "Pechay Baguio"
            ],
            "Pole Sitao" => [
                "3-4 Small Bundles"
            ],
            "Squash" => [
                "Suprema Variety"
            ],
            "Tomato" => [
                "15-18 pcs/kg"
            ],
            "Bell Pepper" => [
                "Green, Local Medium (151-250gm/pc)",
                "Red, Local Medium (151-250gm/pc)"
            ],
            "Broccoli" => [
                "Local Medium (8-10 cm diameter/bunch hd)"
            ],
            "Cauliflower" => [
                "Local Medium (8-10 cm diameter/bunch hd)"
            ],
            "Cabbage" => [
                "Rare Ball, 510 gm - 1 kg/head",
                "Scorpio, 750 gm - 1 kg/head",
                "Wonder Ball, 510 gm - 1 kg/head"
            ],
            "Carrots" => [
                "Local 8-10 pcs/kg"
            ],
            "Celery" => [
                "Medium (501-800 g)"
            ],
            "Chayote" => [
                "Medium (301-400 g)"
            ],
            "Habichuelas/Baguio Beans" => [
                "Habichuelas/Baguio Beans"
            ],
            "Lettuce" => [
                "Green Ice",
                "Iceberg, Medium (301-450 cm diameter/bunch hd)",
                "Romaine"
            ],
            "White Potato" => [
                "Local 10-12 pcs/kg"
            ],
            // Spices
            "Chili" => [
                // "Green Siling Labuyo/Palay",
                // "Red Siling Labuyo/Palay",
                "Green Siling Haba",
                // "Red Siling Haba"
            ],
            "Ginger" => [
                "Hawaiian",
                "Native"
            ],
            "Garlic" => [
                "Imported",
                "Native"
            ],
            "Red Onion" => [
                "Imported Medium (50-100 g)",
                "Native Medium (50-100 g)"
            ],
            "White Onion" => [
                "Imported Medium (50-100 g)"
            ],
            "Spring Onion" => [
                "Spring Onion"
            ],
            // Highland Vegetables
            "Sweet Potato" => [
                "Kamote, Yellow Flesh",
                "Kamote, White Flesh",
                "Kamote, Purple Flesh"
            ],
            "Radish" => [
                "White Radish/Labanos",
                "Red Radish"
            ],
            "Leeks" => [
                "Medium (301-500 g)"
            ],
            "Mustard Greens" => [
                "Mustasa, 3-4 Small Bundles"
            ],
            "Snap Peas" => [
                "Local"
            ],
            "Snow Peas" => [
                "Local"
            ],
            "Turnip" => [
                "Singkamas, Medium (301-500 g)"
            ],
        ];

        $allowedCommodities = $this->getAllowedCommodities();
        $filteredCommodities = array_filter(
            $commodities,
            fn($commodityName) => in_array($commodityName, $allowedCommodities),
            ARRAY_FILTER_USE_KEY
        );

        $this->command->info("Seeding " . count($filteredCommodities) . " out of " . count($commodities) . " available commodities...");

        foreach ($filteredCommodities as $commodityName => $variants) {
            $commodity = Commodity::create([
                "name" => $commodityName,
            ]);

            $commodity->variants()->createMany(collect($variants)->map(fn($variant) => ["name" => $variant])->toArray());

            $this->command->info("✓ Created commodity: {$commodityName} with " . count($variants) . " variant(s)");
        }

        $this->command->info("\n✅ Commodity seeding completed!");

        // Show which commodities were skipped
        $skipped = array_diff(array_keys($commodities), $allowedCommodities);
        if (!empty($skipped)) {
            $this->command->warn("\n⏭️  Skipped commodities: " . implode(', ', $skipped));
        }
    }
}


// "Rice", "Corn", "Ampalaya", "Eggplant", "Pechay", "Pechay Baguio", "Pole Sitao", "Squash", "Tomato", "Bell Pepper", "Broccoli", "Cauliflower", "Cabbage", "Carrots", "Celery", "Chayote", "Habichuelas/Baguio Beans", "Lettuce", "White Potato"
