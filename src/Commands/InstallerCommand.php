<?php

namespace SoftCortex\Installer\Commands;

use Illuminate\Console\Command;
use SoftCortex\Installer\Services\InstallerService;

class InstallerCommand extends Command
{
    public $signature = 'installer:unlock {--force : Skip confirmation prompt}';

    public $description = 'Reset installation state to allow re-installation';

    public function handle(InstallerService $installer): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will reset the installation state. Are you sure?')) {
                $this->info('Operation cancelled.');

                return self::FAILURE;
            }
        }

        $installer->markAsNotInstalled();

        $this->info('Installation state has been reset.');
        $this->info('You can now access the installer at: '.url('/install'));

        return self::SUCCESS;
    }
}
