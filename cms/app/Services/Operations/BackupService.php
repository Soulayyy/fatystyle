<?php

namespace App\Services\Operations;

use App\Models\Backup;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    public function __construct(private readonly Filesystem $files) {}

    public function create(string $type = 'full', ?int $userId = null): Backup
    {
        if (! in_array($type, ['database', 'full'], true)) {
            throw new RuntimeException('Type de sauvegarde invalide.');
        }

        $backup = Backup::create(['type' => $type, 'status' => 'running', 'created_by' => $userId ?? auth()->id()]);
        $directory = storage_path('app/private/backups');
        $this->files->ensureDirectoryExists($directory, 0750, true);
        $base = 'fatystyle-'.now()->format('Ymd-His').'-'.$backup->id;
        $dump = $directory.'/'.$base.'.dump';

        try {
            $this->runDatabaseDump($dump);
            $path = $dump;

            if ($type === 'full') {
                $path = $directory.'/'.$base.'.zip';
                $this->createArchive($path, $dump);
                $this->files->delete($dump);
            }

            $backup->update([
                'status' => 'completed',
                'path' => $path,
                'size_bytes' => filesize($path),
                'sha256' => hash_file('sha256', $path),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $this->files->delete([$dump, $directory.'/'.$base.'.zip']);
            $backup->update(['status' => 'failed', 'error' => $exception->getMessage()]);
            throw $exception;
        }

        return $backup->refresh();
    }

    public function restore(Backup $backup): void
    {
        if ($backup->status !== 'completed' || ! $backup->path || ! is_file($backup->path)) {
            throw new RuntimeException('Sauvegarde indisponible.');
        }
        if (! hash_equals((string) $backup->sha256, hash_file('sha256', $backup->path))) {
            throw new RuntimeException('L’intégrité de la sauvegarde est invalide.');
        }

        $dump = $backup->path;
        $temporary = null;
        if ($backup->type === 'full') {
            $zip = new ZipArchive;
            if ($zip->open($backup->path) !== true) {
                throw new RuntimeException('Archive illisible.');
            }
            $temporary = storage_path('app/private/backups/restore-'.$backup->id.'.dump');
            $stream = $zip->getStream('database.dump');
            if (! $stream) {
                throw new RuntimeException('Dump de base absent de l’archive.');
            }
            $target = fopen($temporary, 'wb');
            stream_copy_to_stream($stream, $target);
            fclose($stream);
            fclose($target);
            $zip->close();
            $dump = $temporary;
        }

        try {
            $process = new Process([
                'pg_restore', '--clean', '--if-exists', '--no-owner', '--no-privileges',
                '--host='.config('database.connections.pgsql.host'),
                '--port='.config('database.connections.pgsql.port'),
                '--username='.config('database.connections.pgsql.username'),
                '--dbname='.config('database.connections.pgsql.database'),
                $dump,
            ], null, ['PGPASSWORD' => (string) config('database.connections.pgsql.password')]);
            $process->setTimeout(600)->mustRun();
            if ($backup->type === 'full') {
                $this->restoreMedia($backup->path);
            }
        } finally {
            if ($temporary) {
                $this->files->delete($temporary);
            }
        }
    }

    private function restoreMedia(string $archive): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archive) !== true) {
            throw new RuntimeException('Archive média illisible.');
        }
        $root = storage_path('app/private/media');
        $this->files->ensureDirectoryExists($root, 0750, true);
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name) || ! str_starts_with($name, 'media/') || str_contains($name, '..')) {
                continue;
            }
            $relative = substr($name, 6);
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }
            $target = $root.'/'.$relative;
            $this->files->ensureDirectoryExists(dirname($target), 0750, true);
            $source = $zip->getStream($name);
            $output = fopen($target, 'wb');
            if (! $source || ! $output) {
                throw new RuntimeException("Impossible de restaurer {$relative}.");
            }
            stream_copy_to_stream($source, $output);
            fclose($source);
            fclose($output);
        }
        $zip->close();
    }

    private function runDatabaseDump(string $target): void
    {
        $process = new Process([
            'pg_dump', '--format=custom', '--no-owner', '--no-privileges',
            '--host='.config('database.connections.pgsql.host'),
            '--port='.config('database.connections.pgsql.port'),
            '--username='.config('database.connections.pgsql.username'),
            '--file='.$target,
            (string) config('database.connections.pgsql.database'),
        ], null, ['PGPASSWORD' => (string) config('database.connections.pgsql.password')]);
        $process->setTimeout(600)->mustRun();
    }

    private function createArchive(string $target, string $dump): void
    {
        $zip = new ZipArchive;
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer l’archive.');
        }
        $zip->addFile($dump, 'database.dump');
        $mediaRoot = storage_path('app/private/media');
        if (is_dir($mediaRoot)) {
            foreach ($this->files->allFiles($mediaRoot) as $file) {
                $zip->addFile($file->getPathname(), 'media/'.$file->getRelativePathname());
            }
        }
        $zip->addFromString('manifest.json', json_encode([
            'created_at' => now()->toIso8601String(),
            'application' => config('app.name'),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $zip->close();
    }
}
