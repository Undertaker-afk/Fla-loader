<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetUserRolesController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->isAdmin()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'Only administrators can view role assignments']
                ]
            ], 403);
        }

        $userId = Arr::get($request->getQueryParams(), 'userId');

        if (!$userId) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '400', 'title' => 'Bad Request', 'detail' => 'userId is required']
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

        $assignments = \Illuminate\Support\Facades\DB::table('fla_loader_role_assignments')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'groupId' => $assignment->group_id,
                    'expiresAt' => $assignment->expires_at,
                    'createdAt' => $assignment->created_at,
                ];
            });

        return new JsonResponse([
            'data' => $assignments
        ], 200);
    }
}
