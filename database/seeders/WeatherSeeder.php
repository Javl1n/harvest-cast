<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RakibDevs\Weather\Weather;

class WeatherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weather = new Weather();

        $info = $weather->getCurrentByZip(env('ZIP_CODE').',ph');

        // dump($info);

        $weather = \App\Models\Weather::create([
            'info' => is_string($info) ? $info : json_encode($info)
        ]);
    }
}
