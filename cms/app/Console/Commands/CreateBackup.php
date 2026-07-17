<?php

namespace App\Console\Commands;

use App\Services\Operations\BackupService;
use Illuminate\Console\Command;

class CreateBackup extends Command
{
    protected $signature = 'cms:backup {--type=full : database ou full}';

    protected $description = 'Crée une sauvegarde vérifiée de Faty Style';

    public function handle(BackupService $backups): int
    {
        $backup = $backups->create((string) $this->option('type'));
        $this->info("Sauvegarde {$backup->id} créée.");

        return self::SUCCESS;
    }
}
