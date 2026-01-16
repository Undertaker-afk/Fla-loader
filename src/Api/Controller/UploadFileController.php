<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Carbon\Carbon;

class UploadFileController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->isAdmin()) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'You do not have permission to upload files']
                ]
            ], 403);
        }

        $uploadedFiles = $request->getUploadedFiles();
        $file = Arr::get($uploadedFiles, 'file');

        if (!$file) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '400', 'title' => 'Bad Request', 'detail' => 'No file provided']
                ]
            ], 400);
        }

        $body = $request->getParsedBody();
        $isPublic = Arr::get($body, 'isPublic', false);
        $allowedGroups = Arr::get($body, 'allowedGroups', []);

        // Create storage directory if not exists
        $storagePath = storage_path('app/fla-loader');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Generate unique filename
        $originalName = $file->getClientFilename();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file extension - only allow safe file types
        $allowedExtensions = ['zip', 'rar', '7z', 'tar', 'gz', 'pdf', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'mp3', 'mp4', 'avi', 'mkv'];
        if (!in_array($extension, $allowedExtensions)) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '400', 'title' => 'Bad Request', 'detail' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions)]
                ]
            ], 400);
        }
        
        $filename = Str::random(32) . '.' . $extension;
        $path = $storagePath . '/' . $filename;

        // Move uploaded file
        $file->moveTo($path);

        // Save to database
        $fileId = \Illuminate\Support\Facades\DB::table('fla_loader_files')->insertGetId([
            'filename' => $filename,
            'original_name' => $originalName,
            'path' => $path,
            'size' => filesize($path),
            'mime_type' => $file->getClientMediaType(),
            'is_public' => $isPublic,
            'allowed_groups' => json_encode($allowedGroups),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return new JsonResponse([
            'data' => [
                'id' => $fileId,
                'filename' => $filename,
                'originalName' => $originalName,
                'isPublic' => $isPublic,
                'allowedGroups' => $allowedGroups,
            ]
        ], 201);
    }
}
