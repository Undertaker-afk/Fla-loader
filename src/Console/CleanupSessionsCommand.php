<?php

namespace Undertaker\FlaLoader\Console;

use Flarum\Console\AbstractCommand;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanupSessionsCommand extends AbstractCommand
{
    protected $signature = 'fla-loader:cleanup-sessions';
    protected $description = 'Remove expired session tokens';

    /**
     * @return void
     */
    protected function fire()
    {
        $this->handle();
    }

    public function handle()
    {
        $this->info('Cleaning up expired session tokens...');

        $count = DB::table('fla_loader_sessions')
            ->where('expires_at', '<=', Carbon::now())
            ->delete();

        $this->info("Deleted {$count} expired session token(s).");
    }
}
