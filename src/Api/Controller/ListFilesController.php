<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListFilesController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->hasPermission('flaLoader.manageFiles')) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'You do not have permission to manage files']
                ]
            ], 403);
        }

        $files = \Illuminate\Support\Facades\DB::table('fla_loader_files')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'filename' => $file->filename,
                    'originalName' => $file->original_name,
                    'size' => $file->size,
                    'mimeType' => $file->mime_type,
                    'isPublic' => (bool) $file->is_public,
                    'allowedGroups' => json_decode($file->allowed_groups, true) ?? [],
                    'createdAt' => $file->created_at,
                ];
            });

        return new JsonResponse([
            'data' => $files
        ], 200);
    }
}
