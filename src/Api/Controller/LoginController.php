<?php

namespace Undertaker\FlaLoader\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class LoginController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $username = Arr::get($body, 'username');
        $password = Arr::get($body, 'password');

        if (!$username || !$password) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '400', 'title' => 'Bad Request', 'detail' => 'Username and password are required']
                ]
            ], 400);
        }

        // Find user by username or email
        $user = User::where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
            return new JsonResponse([
                'errors' => [
                    ['status' => '401', 'title' => 'Unauthorized', 'detail' => 'Invalid credentials']
                ]
            ], 401);
        }

        // Generate session token
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addDays(30);

        // Store session token
        \Illuminate\Support\Facades\DB::table('fla_loader_sessions')->insert([
            'token' => $token,
            'user_id' => $user->id,
            'expires_at' => $expiresAt,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Get user groups/roles
        $groups = $user->groups->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name_singular,
                'namePlural' => $group->name_plural,
                'color' => $group->color,
                'icon' => $group->icon,
            ];
        });

        return new JsonResponse([
            'data' => [
                'token' => $token,
                'expiresAt' => $expiresAt->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'displayName' => $user->display_name,
                    'email' => $user->email,
                ],
                'groups' => $groups,
            ]
        ], 200);
    }
}
