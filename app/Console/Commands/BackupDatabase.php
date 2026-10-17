<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Daily database backup, written in plain PHP (works on shared hosting where mysqldump/exec are blocked).
 * Kept in storage/app/backups (not reachable from the web) for 14 days, and sent to the owner's
 * Telegram when the order-alert bot is set up, so a copy also lives off the server.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=14 : days to keep}';

    protected $description = 'Save a compressed copy of the whole database (and send it to Telegram if set up)';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");

        $file = $dir . '/db-' . now('Asia/Dhaka')->format('Y-m-d-His') . '.sql.gz';
        $gz = gzopen($file, 'wb6');
        $pdo = DB::connection()->getPdo();

        gzwrite($gz, "-- Eternity Gadget database backup " . now('Asia/Dhaka')->toDateTimeString() . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = array_map(fn ($row) => array_values((array) $row)[0], DB::select('SHOW TABLES'));
        foreach ($tables as $table) {
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n" . ($create['Create Table'] ?? array_values($create)[1]) . ";\n\n");

            DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($gz, $table, $pdo) {
                $values = [];
                foreach ($rows as $row) {
                    $values[] = '(' . implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), (array) $row)) . ')';
                }
                $columns = '`' . implode('`,`', array_keys((array) $rows->first())) . '`';
                gzwrite($gz, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $values) . ";\n");
            });
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);

        // Keep the last N days
        foreach (glob($dir . '/db-*.sql.gz') as $old) {
            if (filemtime($old) < now()->subDays((int) $this->option('keep'))->getTimestamp()) {
                @unlink($old);
            }
        }

        $size = round(filesize($file) / 1024) . ' KB';
        $this->info("Backup saved: {$file} ({$size})");

        $token = trim((string) setting('telegram_bot_token', ''));
        $chat = trim((string) setting('telegram_chat_id', ''));
        if ($token !== '' && $chat !== '' && filesize($file) < 45 * 1024 * 1024) {
            try {
                Http::timeout(60)->attach('document', file_get_contents($file), basename($file))
                    ->post("https://api.telegram.org/bot{$token}/sendDocument", [
                        'chat_id' => $chat,
                        'caption' => '🗄 Eternity Gadget ডাটাবেস ব্যাকআপ — ' . now('Asia/Dhaka')->format('d M Y') . " ({$size})",
                    ])->throw();
                $this->info('Sent to Telegram.');
            } catch (\Throwable $e) {
                Log::warning('Backup could not be sent to Telegram', ['message' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
