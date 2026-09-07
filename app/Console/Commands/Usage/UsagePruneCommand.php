<?php

namespace App\Console\Commands\Usage;

use App\Models\UsageEvent;
use Illuminate\Console\Command;

class UsagePruneCommand extends Command
{
    protected $signature = 'usage:prune {--days=90 : Antigüedad máxima en días del detalle de uso}';

    protected $description = 'Purga los usage_events más viejos que la retención configurada';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('La retención debe ser de al menos 1 día.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = UsageEvent::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("usage_events: purgados {$deleted} registros anteriores a {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
