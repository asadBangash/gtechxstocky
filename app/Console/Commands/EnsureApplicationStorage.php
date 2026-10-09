<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Prepare storage/bootstrap folders and public/storage link without calling exec().
 * Use on shared hosts where "php artisan storage:link" fails (exec/symlink disabled in PHP).
 */
class EnsureApplicationStorage extends Command
{
    protected $signature = 'app:ensure-storage {--force : Replace an existing public/storage path}';

    protected $description = 'Create required storage directories and link public/storage (no exec)';

    public function handle(): int
    {
        $this->ensureDirectories();

        if ($this->linkPublicStorage()) {
            $this->info('public/storage is linked to storage/app/public.');
        } else {
            $this->warn('Could not create the link from PHP. Run the shell command printed above, then re-run this command.');
        }

        return self::SUCCESS;
    }

    private function ensureDirectories(): void
    {
        $dirs = [
            storage_path('app/public'),
            storage_path('app/public/backup'),
            storage_path('app/updater'),
            storage_path('app/updates'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->line('Created: '.$dir);
            }
        }
    }

    private function linkPublicStorage(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        $targetReal = realpath($target) ?: $target;

        if (file_exists($link) || is_link($link)) {
            if ($this->publicStorageAlreadyLinked($link, $targetReal)) {
                return true;
            }

            if (is_link($link)) {
                if (! $this->option('force')) {
                    $this->warn('public/storage exists but points elsewhere. Use --force to replace.');

                    return false;
                }
                unlink($link);
            } elseif (is_dir($link)) {
                if (! $this->option('force')) {
                    $this->warn('public/storage is a folder/junction that does not resolve to storage/app/public. Use --force after fixing or removing it.');

                    return false;
                }
                if (is_link($link)) {
                    unlink($link);
                } else {
                    @rmdir($link);
                }
            } else {
                if (! $this->option('force')) {
                    return false;
                }
                @unlink($link);
            }
        }

        if (function_exists('symlink')) {
            if (@symlink($targetReal, $link)) {
                return true;
            }
            $this->warn('PHP symlink() failed: '.(error_get_last()['message'] ?? 'unknown'));
        } else {
            $this->warn('PHP function symlink() is disabled on this server.');
        }

        $relativeTarget = $this->relativeLinkTarget($link, $targetReal);
        $this->newLine();
        $this->line('Run this from your project root in SSH:');
        $this->comment('  rm -rf public/storage && ln -s '.$relativeTarget.' public/storage');
        $this->newLine();
        if (windows_os()) {
            $this->line('On Windows (Admin CMD from project root):');
            $this->comment('  mklink /D public\\storage '.str_replace('/', '\\', $targetReal));
        }

        return $this->publicStorageAlreadyLinked($link, $targetReal);
    }

    private function relativeLinkTarget(string $linkPath, string $targetPath): string
    {
        $linkDir = dirname($linkPath);
        $relative = str_replace('\\', '/', (string) relative_path($linkDir, $targetPath));

        return $relative === '' ? '.' : $relative;
    }

    private function pathsMatch(string $a, string $b): bool
    {
        $na = str_replace('\\', '/', realpath($a) ?: $a);
        $nb = str_replace('\\', '/', realpath($b) ?: $b);

        return rtrim($na, '/') === rtrim($nb, '/');
    }

    /** Symlink (Linux) or directory junction (Windows — is_dir() is often false) already linked. */
    private function publicStorageAlreadyLinked(string $link, string $targetReal): bool
    {
        if (is_link($link)) {
            return $this->pathsMatch(readlink($link), $targetReal);
        }

        if (! file_exists($link) && ! is_dir($link)) {
            return false;
        }

        $resolved = realpath($link);
        if ($resolved && $this->pathsMatch($resolved, $targetReal)) {
            return true;
        }

        if (is_dir($link) && ! is_link($link)) {
            $linkNorm = str_replace('\\', '/', $link);
            $targetNorm = str_replace('\\', '/', $targetReal);

            return rtrim($linkNorm, '/') === rtrim($targetNorm, '/');
        }

        return false;
    }
}
