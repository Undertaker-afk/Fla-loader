<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListUserFilesController implements RequestHandlerInterface
{
    /**
     * List files accessible to the current user
     * 
     * Returns only files that the user has permission to access based on:
     * - Public visibility
     * - Group membership matching allowed groups
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if ($actor->isGuest()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '401', 'title' => 'Unauthorized', 'detail' => 'Authentication required']
                ]
            ], 401);
        }

        // Check if user has permission to download files (admins bypass this check)
        if (!$actor->isAdmin() && !$actor->can('flaLoader.downloadFiles')) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'You do not have permission to access files']
                ]
            ], 403);
        }

        $userGroupIds = $actor->groups->pluck('id')->toArray();

        $files = \Illuminate\Support\Facades\DB::table('fla_loader_files')
            ->get()
            ->filter(function ($file) use ($userGroupIds) {
                // Include public files
                if ($file->is_public) {
                    return true;
                }
                
                // Include files where user has required group
                $allowedGroups = json_decode($file->allowed_groups, true) ?? [];
                if (!empty($allowedGroups)) {
                    return !empty(array_intersect($userGroupIds, $allowedGroups));
                }
                
                return false;
            })
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'filename' => $file->filename,
                    'originalName' => $file->original_name,
                    'size' => $file->size,
                    'mimeType' => $file->mime_type,
                    'isPublic' => (bool) $file->is_public,
                    'createdAt' => $file->created_at,
                ];
            })
            ->values();

        return new JsonResponse([
            'data' => $files
        ], 200);
    }
}
