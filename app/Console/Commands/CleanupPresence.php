<?php

namespace App\Console\Commands;

use App\Models\WorkspacePresence;
use Illuminate\Console\Command;

class CleanupPresence extends Command
{
    protected $signature = 'presence:cleanup';
    protected $description = 'Удаление мёртвых presence записей';

    public function handle(): int
    {
        // Удаляем записи старше 2 минут
        $deleted = WorkspacePresence::where('last_seen', '<', now()->subMinutes(2))
            ->delete();

        if ($deleted > 0) {
            $this->info("Удалено {$deleted} мёртвых записей");
        }

        return self::SUCCESS;
    }
}
