<?php

namespace App\Providers;

use App\Services\DatabaseBackupService;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deutsche Datums-/Wochentagsnamen (z. B. "Montag, 10.08.2026").
        Carbon::setLocale(config('app.locale'));

        $this->backupBeforeMigrations();
    }

    /**
     * Im Produktivbetrieb vor jedem `php artisan migrate` automatisch eine
     * Sicherung erstellen. Schlägt die Sicherung fehl, wird gewarnt – die
     * Migration selbst wird aber nicht blockiert (z. B. falls mysqldump fehlt).
     */
    private function backupBeforeMigrations(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        Event::listen(MigrationsStarted::class, function (): void {
            try {
                $filename = app(DatabaseBackupService::class)->create();

                if ($this->app->runningInConsole()) {
                    fwrite(STDOUT, "Automatische Sicherung vor Migration erstellt: {$filename}".PHP_EOL);
                }
            } catch (Throwable $e) {
                report($e);

                if ($this->app->runningInConsole()) {
                    fwrite(STDERR, 'WARNUNG: Automatische Sicherung fehlgeschlagen: '.$e->getMessage().PHP_EOL);
                }
            }
        });
    }
}
