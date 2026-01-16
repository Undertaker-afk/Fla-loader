<?php

use Flarum\Extend;
use Undertaker\FlaLoader\Api\Controller;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->post('/fla-loader/login', 'fla-loader.login', Controller\LoginController::class)
        ->get('/fla-loader/download/{id}', 'fla-loader.download', Controller\DownloadController::class)
        ->get('/fla-loader/files', 'fla-loader.files.list', Controller\ListFilesController::class),

    (new Extend\Routes('api'))
        ->post('/fla-loader/files', 'fla-loader.files.upload', Controller\UploadFileController::class)
        ->delete('/fla-loader/files/{id}', 'fla-loader.files.delete', Controller\DeleteFileController::class)
        ->patch('/fla-loader/files/{id}', 'fla-loader.files.update', Controller\UpdateFileController::class),

    (new Extend\Routes('api'))
        ->post('/fla-loader/roles', 'fla-loader.roles.assign', Controller\AssignRoleController::class)
        ->get('/fla-loader/roles/{userId}', 'fla-loader.roles.get', Controller\GetUserRolesController::class),

    (new Extend\Settings())
        ->serializeToForum('flaLoader.publicFileId', 'fla-loader.public_file_id'),

    (new Extend\Console())
        ->command(Undertaker\FlaLoader\Console\ExpireRolesCommand::class),

    (new Extend\ServiceProvider())
        ->register(Undertaker\FlaLoader\FlaLoaderServiceProvider::class),
];
