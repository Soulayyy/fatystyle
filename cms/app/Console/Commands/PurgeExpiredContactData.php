<?php

namespace App\Console\Commands;

use App\Models\ContactRequest;
use Illuminate\Console\Command;

class PurgeExpiredContactData extends Command
{
    protected $signature = 'cms:purge-contact-data {--dry-run}';

    protected $description = 'Supprime définitivement les demandes de contact au-delà de la durée de conservation RGPD';

    public function handle(): int
    {
        $threshold = now()->subMonths((int) config('cms.contact_retention_months'));
        $query = ContactRequest::withTrashed()->where('received_at', '<', $threshold);
        $count = $query->count();
        if (! $this->option('dry-run')) {
            $query->forceDelete();
        }
        $this->info("{$count} demande(s) concernée(s).");

        return self::SUCCESS;
    }
}
