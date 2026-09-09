<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncPackagesAndPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-packages-and-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-shot production deploy step for the package/subscription catalog + delete-permission lockdown: runs pending migrations, reseeds the package catalog, and resyncs permissions. Safe to re-run any number of times (idempotent).';

    public function handle()
    {
        $this->call('migrate', ['--force' => true]);

        $this->call('db:seed', ['--class' => 'IntroPackageCatalogSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);

        $this->info('Packages, permissions, and the delete-permission lockdown are up to date.');

        return self::SUCCESS;
    }
}
