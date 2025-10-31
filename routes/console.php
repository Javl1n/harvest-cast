<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use RakibDevs\Weather\Weather;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scrape:today', function () {
    $result = Process::run(
        'python3 ./python/scrape.py',
    );

    $this->error($result->errorOutput());
    $this->comment($result->output());
})->schedule()->daily();

Artisan::command('scrape:all', function () {
    $result = Process::run(
        'python3 ./python/scrape_all.py',
    );

    $this->error($result->errorOutput());
    $this->comment($result->output());
});

Artisan::command('weather:current', function () {
    $weather = new Weather;

    $info = $weather->getCurrentByZip(env('ZIP_CODE').',ph');

    // dump($info);

    $weather = App\Models\Weather::create([
        'info' => is_string($info) ? $info : json_encode($info),
    ]);

    // dump($weather);
    // $this->comment($info);
});

Artisan::command('crop-images:cleanup', function () {
    Artisan::call('crop-images:cleanup --days=30');
    $this->comment('Crop images cleaned up successfully.');
})->schedule()->daily();
