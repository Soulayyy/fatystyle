<?php

namespace App\Console\Commands;

use App\Services\Import\LegacyContentImporter;
use Illuminate\Console\Command;

class ImportLegacyContent extends Command
{
    protected $signature = 'cms:import-public-content
        {path=../data/content.json : Chemin du fichier JSON public}
        {--dry-run : Valider et simuler l’import sans conserver les écritures}';

    protected $description = 'Importe le contenu public existant dans le CMS sans modifier le site en ligne';

    public function handle(LegacyContentImporter $importer): int
    {
        $stats = $importer->import((string) $this->argument('path'), (bool) $this->option('dry-run'));

        $this->table(['Type', 'Nombre'], collect($stats)->map(fn ($value, $key) => [$key, $value]));
        $this->info($this->option('dry-run') ? 'Simulation terminée, aucune écriture conservée.' : 'Import terminé.');

        return self::SUCCESS;
    }
}
