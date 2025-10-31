<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Schedule;
use App\Models\Sensor;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensors = Sensor::all();

        if ($sensors->isEmpty()) {
            $this->command->warn('No sensors found. Please ensure sensors are created before running this seeder.');

            return;
        }

        $commodities = Commodity::all();

        if ($commodities->isEmpty()) {
            $this->command->warn('No commodities found. Please run CommoditySeeder first.');

            return;
        }

        // Crop growing periods in days
        $cropGrowingPeriods = [
            'Rice' => 120,
            'Corn' => 90,
            'Ampalaya' => 60,
            'Eggplant' => 80,
            'Pechay' => 45,
            'Pechay Baguio' => 50,
            'Pole Sitao' => 55,
            'Squash' => 70,
            'Tomato' => 75,
            'Bell Pepper' => 85,
            'Broccoli' => 70,
            'Cauliflower' => 75,
            'Cabbage' => 80,
            'Carrots' => 90,
            'Celery' => 100,
            'Chayote' => 120,
            'Habichuelas/Baguio Beans' => 60,
            'Lettuce' => 50,
            'White Potato' => 100,
        ];

        // Average yield per acre (kg) - converted from hectare by dividing by 2.47105
        $averageYields = [
            'Rice' => 1619,           // 4000 / 2.47105
            'Corn' => 2226,           // 5500 / 2.47105
            'Ampalaya' => 3238,       // 8000 / 2.47105
            'Eggplant' => 4857,       // 12000 / 2.47105
            'Pechay' => 6070,         // 15000 / 2.47105
            'Pechay Baguio' => 5665,  // 14000 / 2.47105
            'Pole Sitao' => 4047,     // 10000 / 2.47105
            'Squash' => 8094,         // 20000 / 2.47105
            'Tomato' => 14166,        // 35000 / 2.47105
            'Bell Pepper' => 6070,    // 15000 / 2.47105
            'Broccoli' => 3642,       // 9000 / 2.47105
            'Cauliflower' => 4047,    // 10000 / 2.47105
            'Cabbage' => 12141,       // 30000 / 2.47105
            'Carrots' => 10118,       // 25000 / 2.47105
            'Celery' => 16189,        // 40000 / 2.47105
            'Chayote' => 7285,        // 18000 / 2.47105
            'Habichuelas/Baguio Beans' => 3238, // 8000 / 2.47105
            'Lettuce' => 8094,        // 20000 / 2.47105
            'White Potato' => 8094,   // 20000 / 2.47105
        ];

        // Average price per kg (PHP) - adjusted based on realistic income per acre research
        // Income per acre = yield per acre × price per kg
        // Field crops: ~₱50,000-85,000/acre | High-value vegetables: ~₱500,000-2,000,000/acre
        $averagePrices = [
            'Rice' => 35,              // ~₱56,665/acre (1,619 kg × ₱35)
            'Corn' => 20,              // ~₱44,520/acre (2,226 kg × ₱20)
            'Ampalaya' => 65,          // ~₱210,470/acre (3,238 kg × ₱65)
            'Eggplant' => 40,          // ~₱194,280/acre (4,857 kg × ₱40)
            'Pechay' => 45,            // ~₱273,150/acre (6,070 kg × ₱45)
            'Pechay Baguio' => 50,     // ~₱283,250/acre (5,665 kg × ₱50)
            'Pole Sitao' => 60,        // ~₱242,820/acre (4,047 kg × ₱60)
            'Squash' => 35,            // ~₱283,290/acre (8,094 kg × ₱35)
            'Tomato' => 50,            // ~₱708,300/acre (14,166 kg × ₱50)
            'Bell Pepper' => 180,      // ~₱1,092,600/acre (6,070 kg × ₱180)
            'Broccoli' => 130,         // ~₱473,460/acre (3,642 kg × ₱130)
            'Cauliflower' => 110,      // ~₱445,170/acre (4,047 kg × ₱110)
            'Cabbage' => 30,           // ~₱364,230/acre (12,141 kg × ₱30)
            'Carrots' => 60,           // ~₱607,080/acre (10,118 kg × ₱60)
            'Celery' => 140,           // ~₱2,266,460/acre (16,189 kg × ₱140)
            'Chayote' => 45,           // ~₱327,825/acre (7,285 kg × ₱45)
            'Habichuelas/Baguio Beans' => 95, // ~₱307,610/acre (3,238 kg × ₱95)
            'Lettuce' => 130,          // ~₱1,052,220/acre (8,094 kg × ₱130, supports 2-3 harvests/year)
            'White Potato' => 55,      // ~₱445,170/acre (8,094 kg × ₱55)
        ];

        $schedulesCreated = 0;

        // First, ensure each crop type has at least 5 completed schedules for yield forecasting
        $minSchedulesPerCropType = 5;

        foreach ($commodities as $commodity) {
            $commodityName = $commodity->name;

            $growingPeriod = $cropGrowingPeriods[$commodityName] ?? 90;
            $expectedYieldPerAcre = $averageYields[$commodityName] ?? 4047;
            $pricePerKg = $averagePrices[$commodityName] ?? 50;

            for ($i = 0; $i < $minSchedulesPerCropType; $i++) {
                $sensor = $sensors->random();

                // All of these should be completed past plantings
                $daysOffset = rand(-365, -($growingPeriod + 30)); // Ensure harvest is also past

                $datePlanted = now()->addDays($daysOffset);
                $expectedHarvestDate = $datePlanted->copy()->addDays($growingPeriod);

                // Randomize farm size (suitable for small sensors)
                $acres = round(rand(5, 30) / 10, 1); // 0.5 to 3.0 acres

                // Calculate seed weight (kg) based on crop type
                // Seeding rates in kg per acre
                $seedWeightPerAcre = match ($commodityName) {
                    'Rice' => 16.19,
                    'Corn' => 8.09,
                    'Tomato' => 0.06,
                    'Cabbage' => 0.16,
                    'Cauliflower' => 0.20,
                    'Broccoli' => 0.16,
                    'Lettuce' => 0.32,
                    'Pechay' => 0.61,
                    'Pechay Baguio' => 0.49,
                    'Carrots' => 1.21,
                    'White Potato' => 728.52,
                    'Bell Pepper' => 0.12,
                    'Eggplant' => 0.08,
                    'Ampalaya' => 1.21,
                    'Pole Sitao' => 20.23,
                    'Squash' => 1.62,
                    'Celery' => 0.20,
                    'Chayote' => 0.81,
                    'Habichuelas/Baguio Beans' => 24.28,
                    default => 2.02,
                };
                $seedWeightKg = round($seedWeightPerAcre * $acres, 2);

                // Calculate expected yield with some variation
                $expectedYield = round($expectedYieldPerAcre * $acres * rand(85, 115) / 100, 2);

                // Calculate expected income
                $expectedIncome = round($expectedYield * $pricePerKg, 2);

                // Add actual harvest data (all are completed)
                $actualHarvestDate = $expectedHarvestDate->copy()->addDays(rand(-7, 7));
                $actualYield = round($expectedYield * rand(70, 120) / 100, 2);
                $actualIncome = round($actualYield * $pricePerKg * rand(90, 110) / 100, 2);

                Schedule::create([
                    'commodity_id' => $commodity->id,
                    'sensor_id' => $sensor->id,
                    'acres' => $acres,
                    'seed_weight_kg' => $seedWeightKg,
                    'date_planted' => $datePlanted,
                    'expected_harvest_date' => $expectedHarvestDate,
                    'actual_harvest_date' => $actualHarvestDate,
                    'expected_yield' => $expectedYield,
                    'yield' => $actualYield,
                    'expected_income' => $expectedIncome,
                    'income' => $actualIncome,
                ]);

                $schedulesCreated++;
            }
        }

        // Then, add additional random schedules per sensor (some current, some past)
        foreach ($sensors as $sensor) {
            // Create 5 additional schedules per sensor for variety
            $additionalSchedulesPerSensor = 5;
            $hasCurrentPlanting = rand(0, 1); // 50% chance of having a current planting

            for ($i = 0; $i < $additionalSchedulesPerSensor; $i++) {
                $commodity = $commodities->random();
                $commodityName = $commodity->name;

                $growingPeriod = $cropGrowingPeriods[$commodityName] ?? 90;
                $expectedYieldPerAcre = $averageYields[$commodityName] ?? 4047;
                $pricePerKg = $averagePrices[$commodityName] ?? 50;

                // Determine if this is the current planting (last iteration and has current planting)
                $isCurrentPlanting = ($i === $additionalSchedulesPerSensor - 1) && $hasCurrentPlanting;

                if ($isCurrentPlanting) {
                    // Current planting: started recently, harvest date in the future
                    $daysOffset = rand(-30, -1); // Planted 1-30 days ago
                } else {
                    // Past plantings: both planting and harvest dates are in the past
                    $daysOffset = rand(-365, -($growingPeriod + 30)); // Ensure harvest is also past
                }

                $datePlanted = now()->addDays($daysOffset);
                $expectedHarvestDate = $datePlanted->copy()->addDays($growingPeriod);

                // Randomize farm size (suitable for small sensors)
                $acres = round(rand(5, 30) / 10, 1); // 0.5 to 3.0 acres

                // Calculate seed weight (kg) based on crop type
                // Seeding rates in kg per acre
                $seedWeightPerAcre = match ($commodityName) {
                    'Rice' => 16.19,
                    'Corn' => 8.09,
                    'Tomato' => 0.06,
                    'Cabbage' => 0.16,
                    'Cauliflower' => 0.20,
                    'Broccoli' => 0.16,
                    'Lettuce' => 0.32,
                    'Pechay' => 0.61,
                    'Pechay Baguio' => 0.49,
                    'Carrots' => 1.21,
                    'White Potato' => 728.52,
                    'Bell Pepper' => 0.12,
                    'Eggplant' => 0.08,
                    'Ampalaya' => 1.21,
                    'Pole Sitao' => 20.23,
                    'Squash' => 1.62,
                    'Celery' => 0.20,
                    'Chayote' => 0.81,
                    'Habichuelas/Baguio Beans' => 24.28,
                    default => 2.02,
                };
                $seedWeightKg = round($seedWeightPerAcre * $acres, 2);

                // Calculate expected yield with some variation
                $expectedYield = round($expectedYieldPerAcre * $acres * rand(85, 115) / 100, 2);

                // Calculate expected income
                $expectedIncome = round($expectedYield * $pricePerKg, 2);

                // For past plantings (completed harvests), add actual harvest data
                $actualHarvestDate = null;
                $actualYield = null;
                $actualIncome = null;

                if (! $isCurrentPlanting && $expectedHarvestDate->isPast()) {
                    // Add some variation to actual harvest date (±7 days)
                    $actualHarvestDate = $expectedHarvestDate->copy()->addDays(rand(-7, 7));

                    // Actual yield varies from expected (70% to 120%)
                    $actualYield = round($expectedYield * rand(70, 120) / 100, 2);

                    // Calculate actual income
                    $actualIncome = round($actualYield * $pricePerKg * rand(90, 110) / 100, 2);
                }

                Schedule::create([
                    'commodity_id' => $commodity->id,
                    'sensor_id' => $sensor->id,
                    'acres' => $acres,
                    'seed_weight_kg' => $seedWeightKg,
                    'date_planted' => $datePlanted,
                    'expected_harvest_date' => $expectedHarvestDate,
                    'actual_harvest_date' => $actualHarvestDate,
                    'expected_yield' => $expectedYield,
                    'yield' => $actualYield,
                    'expected_income' => $expectedIncome,
                    'income' => $actualIncome,
                ]);

                $schedulesCreated++;
            }
        }

        $this->command->info("Created {$schedulesCreated} planting schedules ({$commodities->count()} crop types × {$minSchedulesPerCropType} = ".($commodities->count() * $minSchedulesPerCropType).' base schedules + '.($sensors->count() * 5).' additional schedules).');
    }
}
