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

class ResetHwidController implements RequestHandlerInterface
{
    /**
     * Reset a user's HWID
     * 
     * Allows administrators to reset a user's hardware ID so they can
     * log in from a different machine.
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
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'Only administrators can reset HWIDs']
                ]
            ], 403);
        }

        $body = $request->getParsedBody();
        $userId = Arr::get($body, 'userId');

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

        // Delete the user's HWID record
        $deleted = \Illuminate\Support\Facades\DB::table('fla_loader_hwid')
            ->where('user_id', $userId)
            ->delete();

        return new JsonResponse([
            'data' => [
                'userId' => $userId,
                'username' => $user->username,
                'hwidReset' => $deleted > 0,
                'message' => $deleted > 0 
                    ? 'HWID reset successfully. User can now login from a new device.'
                    : 'No HWID was registered for this user.'
            ]
        ], 200);
    }
}
