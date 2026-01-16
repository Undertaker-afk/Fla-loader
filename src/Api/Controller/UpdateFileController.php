<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Carbon\Carbon;

class UpdateFileController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->hasPermission('flaLoader.manageFiles')) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '403', 'title' => 'Forbidden', 'detail' => 'You do not have permission to update files']
                ]
            ], 403);
        }

        $fileId = Arr::get($request->getQueryParams(), 'id');
        $body = $request->getParsedBody();

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

        $updateData = [];
        
        if (isset($body['isPublic'])) {
            $updateData['is_public'] = $body['isPublic'];
        }
        
        if (isset($body['allowedGroups'])) {
            $updateData['allowed_groups'] = json_encode($body['allowedGroups']);
        }
        
        $updateData['updated_at'] = Carbon::now();

        \Illuminate\Support\Facades\DB::table('fla_loader_files')
            ->where('id', $fileId)
            ->update($updateData);

        return new JsonResponse([
            'data' => [
                'id' => $fileId,
                'success' => true
            ]
        ], 200);
    }
}
