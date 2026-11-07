<?php

use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('DB_URL=');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Schema verification requires isolated in-memory SQLite.');
}
Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
Artisan::call('db:seed', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
if (User::count() !== 3 || Job::count() !== 2 || Skill::count() !== 7) {
    throw new RuntimeException('Seed repeatability failed.');
}
echo 'Repeat seeding: 3 users, 2 jobs, 7 skills; '.count(Schema::getTableListing())." tables.\n";
Artisan::call('migrate:rollback', ['--force' => true]);
echo Artisan::output();
if (Schema::hasTable('jobs') || Schema::hasTable('contracts') || Schema::hasTable('users')) {
    throw new RuntimeException('Rollback did not remove domain tables.');
}
Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
echo "Fresh migrate, seed twice, rollback and remigrate: PASS (memory only).\n";
