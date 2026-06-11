<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Erstellt und verwaltet lokale SQL-Sicherungen der Datenbank per mysqldump.
 * Sicherungen liegen unter storage/app/backups (per .gitignore vom Repo
 * ausgeschlossen – sie enthalten Personendaten und gehören NICHT ins Git).
 */
class DatabaseBackupService
{
    /** Erlaubtes Namensschema – schützt Download/Löschen vor Pfad-Manipulation. */
    public const FILENAME_PATTERN = '/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';

    /** Maximale Laufzeit für Dump/Restore in Sekunden. */
    private const TIMEOUT = 300;

    /**
     * Verzeichnis der Sicherungen (wird bei Bedarf angelegt).
     */
    public function directory(): string
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Erstellt eine neue Sicherung und gibt den Dateinamen zurück.
     */
    public function create(?string $timestamp = null): string
    {
        $config = $this->connectionConfig();
        $filename = 'backup_'.($timestamp ?? Carbon::now()->format('Y-m-d_H-i-s')).'.sql';
        $path = $this->directory().'/'.$filename;

        $process = new Process([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.(string) $config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--skip-lock-tables',
            '--routines',
            '--events',
            $config['database'],
        ], null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);

        $process->setTimeout(self::TIMEOUT);

        $handle = fopen($path, 'w');

        try {
            $process->run(function (string $type, string $buffer) use ($handle): void {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } catch (Throwable $e) {
            fclose($handle);
            @unlink($path);

            throw new RuntimeException('Backup fehlgeschlagen: '.$e->getMessage(), 0, $e);
        }

        fclose($handle);

        if (! $process->isSuccessful()) {
            @unlink($path);

            throw new RuntimeException('Backup fehlgeschlagen: '.trim($process->getErrorOutput()));
        }

        return $filename;
    }

    /**
     * Alle vorhandenen Sicherungen, neueste zuerst.
     *
     * @return Collection<int, array{filename: string, size: int, size_human: string, date: Carbon}>
     */
    public function all(): Collection
    {
        $files = glob($this->directory().'/*.sql') ?: [];

        return collect($files)
            ->map(fn (string $path): array => [
                'filename' => basename($path),
                'size' => (int) filesize($path),
                'size_human' => $this->humanSize((int) filesize($path)),
                'date' => Carbon::createFromTimestamp(filemtime($path)),
            ])
            ->sortByDesc(fn (array $backup): int => $backup['date']->timestamp)
            ->values();
    }

    /**
     * Vollständiger Pfad zu einer Sicherung (mit Namensprüfung).
     */
    public function path(string $filename): string
    {
        if (! preg_match(self::FILENAME_PATTERN, $filename)) {
            throw new RuntimeException('Ungültiger Dateiname.');
        }

        return $this->directory().'/'.$filename;
    }

    public function exists(string $filename): bool
    {
        return (bool) preg_match(self::FILENAME_PATTERN, $filename)
            && is_file($this->directory().'/'.$filename);
    }

    public function delete(string $filename): void
    {
        $path = $this->path($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Spielt eine SQL-Sicherung ein und ersetzt damit den aktuellen Datenbestand.
     */
    public function restore(string $sqlFilePath): void
    {
        if (! is_file($sqlFilePath)) {
            throw new RuntimeException('Sicherungsdatei nicht gefunden.');
        }

        $config = $this->connectionConfig();

        $process = new Process([
            'mysql',
            '--host='.$config['host'],
            '--port='.(string) $config['port'],
            '--user='.$config['username'],
            $config['database'],
        ], null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);

        $process->setTimeout(self::TIMEOUT);

        $input = fopen($sqlFilePath, 'r');
        $process->setInput($input);
        $process->run();

        if (is_resource($input)) {
            fclose($input);
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Wiederherstellung fehlgeschlagen: '.trim($process->getErrorOutput()));
        }
    }

    /**
     * Konfiguration der Standard-Datenbankverbindung.
     *
     * @return array<string, mixed>
     */
    private function connectionConfig(): array
    {
        return config('database.connections.'.config('database.default'));
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? $bytes : number_format($bytes, 1)).' '.$units[$i];
    }
}
