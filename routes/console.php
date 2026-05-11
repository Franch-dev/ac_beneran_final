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

    $this->components->info('Wiping ac_anggota database');
    Artisan::call('db:wipe', [
        '--database' => 'ac_anggota',
        '--force' => true,
    ]);
    $this->output->write(Artisan::output());

    $this->components->info('Running migrations (per database)');
    foreach (
        [
            'main' => 'database/migrations/main',
            'ac_service' => 'database/migrations/ac_service',
            'ac_anggota' => 'database/migrations/ac_anggota',
        ] as $database => $path
    ) {
        Artisan::call('migrate', [
            '--database' => $database,
            '--path' => $path,
            '--force' => true,
        ]);
        $this->output->write(Artisan::output());
    }

    if ($this->option('seed')) {
        $this->components->info('Seeding databases');
        Artisan::call('db:seed', ['--force' => true]);
        $this->output->write(Artisan::output());
    }
})->purpose('Refresh main, ac_service, and ac_anggota databases for this multi-database app');
