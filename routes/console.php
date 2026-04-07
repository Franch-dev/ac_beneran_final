<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:refresh-databases {--seed}', function () {
    $this->components->info('Wiping ac_service database');
    Artisan::call('db:wipe', [
        '--database' => 'ac_service',
        '--force' => true,
    ]);
    $this->output->write(Artisan::output());

    $this->components->info('Wiping main database');
    Artisan::call('db:wipe', [
        '--database' => 'main',
        '--force' => true,
    ]);
    $this->output->write(Artisan::output());

    $this->components->info('Running migrations');
    Artisan::call('migrate', ['--force' => true]);
    $this->output->write(Artisan::output());

    if ($this->option('seed')) {
        $this->components->info('Seeding databases');
        Artisan::call('db:seed', ['--force' => true]);
        $this->output->write(Artisan::output());
    }
})->purpose('Refresh the main and ac_service databases for this multi-database app');
