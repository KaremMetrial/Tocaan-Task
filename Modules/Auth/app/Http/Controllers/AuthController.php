<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Core\Http\Controllers\ApiController;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    /**
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        $token = JWTAuth::fromUser($user);

        return $this->createdResponse(
            $this->tokenPayload($token, $user),
            'Registration successful.',
        );
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = auth('api')->attempt($request->validated());

        if ($token === false) {
            return $this->errorResponse('Invalid credentials.', 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        return $this->successResponse(
            $this->tokenPayload($token, $user),
            'Login successful.',
        );
    }

    /**
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        return $this->successResponse(
            new UserResource(auth('api')->user()),
            'Authenticated user retrieved.',
        );
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return $this->successResponse(null, 'Successfully logged out.');
    }

    /**
     * Build the standard token + user payload.
     *
     * @return array<string, mixed>
     */
    private function tokenPayload(string $token, User $user): array
    {
        return [
            'user' => new UserResource($user),
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ];
    }
}
