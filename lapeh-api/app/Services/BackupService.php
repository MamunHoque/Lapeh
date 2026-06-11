<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Creates and manages database backups under storage/app/backups (outside the
 * public web root). MySQL uses mysqldump; SQLite (used in tests) is copied.
 */
class BackupService
{
    private string $dir;

    public function __construct()
    {
        $this->dir = storage_path('app/backups');
        if (! is_dir($this->dir)) {
            @mkdir($this->dir, 0750, true);
        }
    }

    /**
     * Produce a new backup file.
     *
     * @return array{ok: bool, message: string, file?: string}
     */
    public function create(): array
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $stamp = Carbon::now()->format('Ymd-His');

        try {
            if ($driver === 'sqlite') {
                $source = config("database.connections.{$connection}.database");
                $file = "lapeh-{$stamp}.sqlite";
                if (! is_file($source) || ! copy($source, "{$this->dir}/{$file}")) {
                    return ['ok' => false, 'message' => 'Could not copy the SQLite database file.'];
                }
                $this->prune();
                return ['ok' => true, 'message' => 'Backup created.', 'file' => $file];
            }

            if ($driver === 'mysql') {
                $cfg = config("database.connections.{$connection}");
                $file = "lapeh-{$stamp}.sql";
                $target = "{$this->dir}/{$file}";

                $process = new Process([
                    'mysqldump',
                    '-h', (string) $cfg['host'],
                    '-P', (string) $cfg['port'],
                    '-u', (string) $cfg['username'],
                    '--password=' . (string) $cfg['password'],
                    '--single-transaction',
                    '--no-tablespaces',
                    (string) $cfg['database'],
                ]);
                $process->setTimeout(300);

                $out = fopen($target, 'w');
                $process->run(function ($type, $buffer) use ($out) {
                    if ($type === Process::OUT) {
                        fwrite($out, $buffer);
                    }
                });
                fclose($out);

                if (! $process->isSuccessful()) {
                    @unlink($target);
                    Log::warning('DB backup failed', ['error' => $process->getErrorOutput()]);
                    return ['ok' => false, 'message' => 'mysqldump failed: ' . trim($process->getErrorOutput())];
                }

                $this->prune();
                return ['ok' => true, 'message' => 'Backup created.', 'file' => $file];
            }

            return ['ok' => false, 'message' => "Unsupported database driver: {$driver}"];
        } catch (\Throwable $e) {
            Log::error('DB backup exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Backup error: ' . $e->getMessage()];
        }
    }

    /** Recent backups, newest first. */
    public function list(): array
    {
        $files = glob("{$this->dir}/lapeh-*") ?: [];
        rsort($files);

        return array_map(fn ($path) => [
            'name' => basename($path),
            'size' => $this->humanSize(filesize($path) ?: 0),
            'created_at' => Carbon::createFromTimestamp(filemtime($path)),
        ], $files);
    }

    public function path(string $name): ?string
    {
        // Prevent path traversal — only allow our generated filenames.
        if (! preg_match('/^lapeh-[\w.\-]+$/', $name)) {
            return null;
        }
        $path = "{$this->dir}/{$name}";
        return is_file($path) ? $path : null;
    }

    public function delete(string $name): bool
    {
        $path = $this->path($name);
        return $path ? @unlink($path) : false;
    }

    public function lastBackupAt(): ?Carbon
    {
        return $this->list()[0]['created_at'] ?? null;
    }

    /** Keep only the most recent N backups. */
    public function prune(int $keep = 10): void
    {
        $files = glob("{$this->dir}/lapeh-*") ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
