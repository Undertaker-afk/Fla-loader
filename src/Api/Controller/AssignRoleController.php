<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Carbon\Carbon;

class AssignRoleController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->isAdmin()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'Only administrators can assign roles']
                ]
            ], 403);
        }

        $body = $request->getParsedBody();
        $userId = Arr::get($body, 'userId');
        $groupId = Arr::get($body, 'groupId');
        $duration = Arr::get($body, 'duration'); // '7d', '30d', '180d', '1y', 'lifetime'

        if (!$userId || !$groupId || !$duration) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '400', 'title' => 'Bad Request', 'detail' => 'userId, groupId, and duration are required']
                ]
            ], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '404', 'title' => 'Not Found', 'detail' => 'User not found']
                ]
            ], 404);
        }

        // Calculate expiration date
        $expiresAt = null;
        if ($duration !== 'lifetime') {
            $expiresAt = Carbon::now();
            switch ($duration) {
                case '7d':
                    $expiresAt->addDays(7);
                    break;
                case '30d':
                    $expiresAt->addDays(30);
                    break;
                case '180d':
                    $expiresAt->addDays(180);
                    break;
                case '1y':
                    $expiresAt->addYear();
                    break;
                default:
                    return new JsonResponse([
                        'errors' => [
                            ['status' => '400', 'title' => 'Bad Request', 'detail' => 'Invalid duration. Use: 7d, 30d, 180d, 1y, or lifetime']
                        ]
                    ], 400);
            }
        }

        // Check if assignment already exists
        $existing = \Illuminate\Support\Facades\DB::table('fla_loader_role_assignments')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();

        if ($existing) {
            // Update existing assignment
            \Illuminate\Support\Facades\DB::table('fla_loader_role_assignments')
                ->where('user_id', $userId)
                ->where('group_id', $groupId)
                ->update([
                    'expires_at' => $expiresAt,
                    'updated_at' => Carbon::now(),
                ]);
        } else {
            // Create new assignment
            \Illuminate\Support\Facades\DB::table('fla_loader_role_assignments')->insert([
                'user_id' => $userId,
                'group_id' => $groupId,
                'expires_at' => $expiresAt,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Add user to group
            $user->groups()->attach($groupId);
        }

        return new JsonResponse([
            'data' => [
                'userId' => $userId,
                'groupId' => $groupId,
                'expiresAt' => $expiresAt ? $expiresAt->toIso8601String() : null,
            ]
        ], 200);
    }
}
