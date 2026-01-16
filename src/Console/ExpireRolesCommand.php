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

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName('fla-loader:expire-roles')
            ->setDescription('Remove expired role assignments');
    }

    /**
     * @return void
     */
    protected function fire()
    {
        $this->handle();
    }

    public function handle()
    {
        $this->info('Checking for expired roles...');

        $expiredAssignments = DB::table('fla_loader_role_assignments')
            ->where('expires_at', '<=', Carbon::now())
            ->whereNotNull('expires_at')
            ->get();

        $count = 0;
        
        if ($expiredAssignments->isEmpty()) {
            $this->info("No expired role assignments found.");
            return;
        }
        
        // Collect all user-group pairs for batch detachment
        $detachments = [];
        $assignmentIds = [];
        
        foreach ($expiredAssignments as $assignment) {
            $detachments[$assignment->user_id][] = $assignment->group_id;
            $assignmentIds[] = $assignment->id;
            $count++;
        }
        
        // Batch detach groups from users
        foreach ($detachments as $userId => $groupIds) {
            $user = User::find($userId);
            if ($user) {
                $user->groups()->detach($groupIds);
                $this->info("Removed expired roles for user {$user->username} (" . count($groupIds) . " group(s))");
            }
        }
        
        // Batch delete assignment records
        DB::table('fla_loader_role_assignments')
            ->whereIn('id', $assignmentIds)
            ->delete();

        $this->info("Expired {$count} role assignment(s).");
    }
}
