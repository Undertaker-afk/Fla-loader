<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteFileController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->isAdmin()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'You do not have permission to delete files']
                ]
            ], 403);
        }

        $fileId = Arr::get($request->getQueryParams(), 'id');
        if (!$fileId) {
            // Try to get from route parameters
            $fileId = Arr::get($request->getAttribute('routeParams', []), 'id');
        }

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

        // Delete physical file
        $storageRoot = realpath(storage_path());
        $fileRealPath = $file->path !== null ? realpath($file->path) : false;

        if ($storageRoot !== false && $fileRealPath !== false) {
            $storageRoot = rtrim($storageRoot, DIRECTORY_SEPARATOR);
            if (strpos($fileRealPath, $storageRoot . DIRECTORY_SEPARATOR) === 0 && file_exists($fileRealPath)) {
                unlink($fileRealPath);
            }
        }

        // Delete from database
        \Illuminate\Support\Facades\DB::table('fla_loader_files')
            ->where('id', $fileId)
            ->delete();

        return new JsonResponse(null, 204);
    }
}
