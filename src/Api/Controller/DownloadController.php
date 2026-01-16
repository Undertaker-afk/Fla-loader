<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DownloadController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $fileId = Arr::get($request->getQueryParams(), 'id');
        if (!$fileId) {
            // Try to get from route parameters
            $fileId = Arr::get($request->getAttribute('routeParams', []), 'id');
        }
        $token = Arr::get($request->getQueryParams(), 'token');

        if (!$fileId) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '400', 'title' => 'Bad Request', 'detail' => 'File ID is required']
                ]
            ], 400);
        }

        // Get file info
        $file = \Illuminate\Support\Facades\DB::table('fla_loader_files')
            ->where('id', $fileId)
            ->first();

        if (!$file) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '404', 'title' => 'Not Found', 'detail' => 'File not found']
                ]
            ], 404);
        }

        // Authenticate user
        $user = null;
        
        if ($token) {
            // Token-based auth for external loader
            $session = \Illuminate\Support\Facades\DB::table('fla_loader_sessions')
                ->where('token', $token)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if ($session) {
                $user = User::find($session->user_id);
            }
        } else {
            // Session-based auth for logged-in users
            $user = RequestUtil::getActor($request);
        }

        if (!$user || $user->isGuest()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '401', 'title' => 'Unauthorized', 'detail' => 'Authentication required']
                ]
            ], 401);
        }

        // Check permissions
        $allowedGroups = json_decode($file->allowed_groups, true) ?? [];
        
        if (!empty($allowedGroups)) {
            $userGroupIds = $user->groups->pluck('id')->toArray();
            $hasAccess = !empty(array_intersect($userGroupIds, $allowedGroups));
            
            if (!$hasAccess) {
                return new JsonResponse([
                    'errors' => [
                        ['status' => '403', 'title' => 'Forbidden', 'detail' => 'You do not have permission to download this file']
                    ]
                ], 403);
            }
        }

        // Return file
        if (!file_exists($file->path)) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '404', 'title' => 'Not Found', 'detail' => 'File not found on disk']
                ]
            ], 404);
        }

        if (!is_readable($file->path)) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '500', 'title' => 'Internal Server Error', 'detail' => 'File is not readable']
                ]
            ], 500);
        }

        // Open file stream
        $fileHandle = fopen($file->path, 'r');
        if (!$fileHandle) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '500', 'title' => 'Internal Server Error', 'detail' => 'Failed to open file']
                ]
            ], 500);
        }

        $response = new \Laminas\Diactoros\Response();
        $stream = new \Laminas\Diactoros\Stream($fileHandle);

        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', $file->mime_type)
            ->withHeader('Content-Disposition', 'attachment; filename="' . $file->original_name . '"')
            ->withHeader('Content-Length', (string) $file->size);
    }
}
