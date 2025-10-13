<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommoditySeeder extends Seeder
{
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
        ];

        foreach ($commodities as $commodityName => $variants) {
            $commodity = Commodity::create([
                "name" => $commodityName,
            ]);

            $commodity->variants()->createMany(collect($variants)->map(fn($variant) => ["name" => $variant])->toArray());
        }
    }
}
