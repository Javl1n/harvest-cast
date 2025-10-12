<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:scrape', function () {
    $result = Process::run(
        'python3 ./python/scrape.py',
    );

    $this->error($result->errorOutput());
    $this->comment($result->output());
})->schedule()->daily();
