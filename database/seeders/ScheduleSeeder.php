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

        // Average yield per hectare (kg)
        $averageYields = [
            'Rice' => 4000,
            'Corn' => 5500,
            'Ampalaya' => 8000,
            'Eggplant' => 12000,
            'Pechay' => 15000,
            'Pechay Baguio' => 14000,
            'Pole Sitao' => 10000,
            'Squash' => 20000,
            'Tomato' => 35000,
            'Bell Pepper' => 15000,
            'Broccoli' => 9000,
            'Cauliflower' => 10000,
            'Cabbage' => 30000,
            'Carrots' => 25000,
            'Celery' => 40000,
            'Chayote' => 18000,
            'Habichuelas/Baguio Beans' => 8000,
            'Lettuce' => 20000,
            'White Potato' => 20000,
        ];

        // Average price per kg (PHP)
        $averagePrices = [
            'Rice' => 45,
            'Corn' => 25,
            'Ampalaya' => 60,
            'Eggplant' => 50,
            'Pechay' => 40,
            'Pechay Baguio' => 45,
            'Pole Sitao' => 55,
            'Squash' => 30,
            'Tomato' => 70,
            'Bell Pepper' => 150,
            'Broccoli' => 100,
            'Cauliflower' => 90,
            'Cabbage' => 35,
            'Carrots' => 80,
            'Celery' => 120,
            'Chayote' => 40,
            'Habichuelas/Baguio Beans' => 90,
            'Lettuce' => 60,
            'White Potato' => 50,
        ];

        $schedulesCreated = 0;

        foreach ($sensors as $sensor) {
            // Create 10 schedules per sensor (9 past schedules, and maybe 1 current)
            $schedulesPerSensor = 10;
            $hasCurrentPlanting = rand(0, 1); // 50% chance of having a current planting

            for ($i = 0; $i < $schedulesPerSensor; $i++) {
                $commodity = $commodities->random();
                $commodityName = $commodity->name;

                $growingPeriod = $cropGrowingPeriods[$commodityName] ?? 90;
                $expectedYieldPerHectare = $averageYields[$commodityName] ?? 10000;
                $pricePerKg = $averagePrices[$commodityName] ?? 50;

                // Determine if this is the current planting (last iteration and has current planting)
                $isCurrentPlanting = ($i === $schedulesPerSensor - 1) && $hasCurrentPlanting;

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
                $hectares = round(rand(5, 30) / 10, 1); // 0.5 to 3.0 hectares

                // Calculate seeds planted based on crop type
                $seedsPerHectare = match ($commodityName) {
                    'Rice' => 40000,
                    'Corn' => 60000,
                    'Tomato' => 25000,
                    'Cabbage', 'Cauliflower', 'Broccoli' => 30000,
                    'Lettuce', 'Pechay' => 50000,
                    default => 35000,
                };
                $seedsPlanted = (int) ($seedsPerHectare * $hectares);

                // Calculate expected yield with some variation
                $expectedYield = round($expectedYieldPerHectare * $hectares * rand(85, 115) / 100, 2);

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
                    'hectares' => $hectares,
                    'seeds_planted' => $seedsPlanted,
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

        $this->command->info("Created {$schedulesCreated} planting schedules for {$sensors->count()} sensors.");
    }
}
