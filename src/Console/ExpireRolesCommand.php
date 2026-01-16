<?php

namespace Undertaker\FlaLoader\Console;

use Flarum\Console\AbstractCommand;
use Flarum\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpireRolesCommand extends AbstractCommand
{
    protected $signature = 'fla-loader:expire-roles';
    protected $description = 'Remove expired role assignments';

    public function handle()
    {
        $this->info('Checking for expired roles...');

        $expiredAssignments = DB::table('fla_loader_role_assignments')
            ->where('expires_at', '<=', Carbon::now())
            ->whereNotNull('expires_at')
            ->get();

        $count = 0;
        foreach ($expiredAssignments as $assignment) {
            $user = User::find($assignment->user_id);
            
            if ($user) {
                // Remove user from group
                $user->groups()->detach($assignment->group_id);
                
                // Delete assignment record
                DB::table('fla_loader_role_assignments')
                    ->where('id', $assignment->id)
                    ->delete();
                
                $count++;
                $this->info("Removed expired role for user {$user->username} (group ID: {$assignment->group_id})");
            }
        }

        $this->info("Expired {$count} role assignment(s).");
    }
}
