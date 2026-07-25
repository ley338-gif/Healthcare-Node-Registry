<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SplFileInfo;
use Throwable;

final class DoctorCommand extends Command
{
    protected $signature = 'registry:doctor
        {--skip-assets : Frontend-Manifest bei Backend-Prüfungen überspringen}';

    protected $description = 'Validate installation readiness';

    public function handle(): int
    {
        $checks = collect();

        $this->record($checks, 'Application key', filled(config('app.key')), 'APP_KEY fehlt.');
        $this->record(
            $checks,
            'Production debug',
            ! app()->isProduction() || config('app.debug') === false,
            'APP_DEBUG muss in Production false sein.',
        );

        $this->checkDatabase($checks);
        $this->checkWritableDirectories($checks);

        if (! $this->option('skip-assets')) {
            $this->record(
                $checks,
                'Frontend build',
                File::isFile(public_path('build/manifest.json')),
                'public/build/manifest.json fehlt.',
            );
        }

        $this->table(
            ['Prüfung', 'Ergebnis', 'Hinweis'],
            $checks->map(fn (array $check): array => [
                $check['name'],
                $check['passed'] ? 'OK' : 'FEHLER',
                $check['message'],
            ])->all(),
        );

        if ($checks->contains(fn (array $check): bool => ! $check['passed'])) {
            $this->error('Installation ist nicht betriebsbereit.');

            return self::FAILURE;
        }

        $this->info('Installation ist für den geprüften Umfang betriebsbereit.');

        return self::SUCCESS;
    }

    /** @param Collection<int, array{name: string, passed: bool, message: string}> $checks */
    private function checkDatabase(Collection $checks): void
    {
        try {
            DB::select('select 1');
            $this->record($checks, 'Database connection', true, 'OK');
        } catch (Throwable) {
            $this->record($checks, 'Database connection', false, 'PostgreSQL nicht erreichbar.');

            return;
        }

        if (! Schema::hasTable('migrations')) {
            $this->record($checks, 'Migrations table', false, 'Migrationstabelle fehlt.');

            return;
        }

        $available = collect(File::files(database_path('migrations')))
            ->map(fn (SplFileInfo $file): string => pathinfo($file->getFilename(), PATHINFO_FILENAME));
        $executed = DB::table('migrations')->pluck('migration');
        $pending = $available->diff($executed);

        $this->record(
            $checks,
            'Pending migrations',
            $pending->isEmpty(),
            $pending->isEmpty() ? 'OK' : 'Ausstehend: '.$pending->implode(', '),
        );

        $adminExists = Schema::hasTable('users')
            && Schema::hasTable('roles')
            && User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'system-administrator'))
                ->exists();

        $this->record(
            $checks,
            'Initial administrator',
            $adminExists,
            'Kein Systemadministrator vorhanden.',
        );
    }

    /** @param Collection<int, array{name: string, passed: bool, message: string}> $checks */
    private function checkWritableDirectories(Collection $checks): void
    {
        foreach ([
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $path) {
            $this->record(
                $checks,
                "Writable: {$path}",
                File::isDirectory($path) && is_writable($path),
                'Verzeichnis fehlt oder ist nicht beschreibbar.',
            );
        }
    }

    /** @param Collection<int, array{name: string, passed: bool, message: string}> $checks */
    private function record(Collection $checks, string $name, bool $passed, string $message): void
    {
        $checks->push([
            'name' => $name,
            'passed' => $passed,
            'message' => $passed ? 'OK' : $message,
        ]);
    }
}
