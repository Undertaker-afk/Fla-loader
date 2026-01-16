<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetHwidController implements RequestHandlerInterface
{
    /**
     * Get a user's HWID status
     * 
     * @param ServerRequestInterface $request The HTTP request
     * @return ResponseInterface JSON response
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->isAdmin()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'Only administrators can view HWIDs']
                ]
            ], 403);
        }

        $userId = Arr::get($request->getAttribute('routeParams', []), 'userId');

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

        $hwid = \Illuminate\Support\Facades\DB::table('fla_loader_hwid')
            ->where('user_id', $userId)
            ->first();

        return new JsonResponse([
            'data' => [
                'userId' => $userId,
                'username' => $user->username,
                'hasHwid' => $hwid !== null,
                'hwid' => $hwid ? substr($hwid->hwid, 0, 8) . '...' : null, // Show only first 8 chars for security
                'registeredAt' => $hwid ? $hwid->created_at : null,
            ]
        ], 200);
    }
}
