<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    /**
     * Register a new user.
     *
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Registrar novo usuário",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuário registrado com sucesso"),
     *     @OA\Response(response=422, description="Dados inválidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->createdResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Login and retrieve an API token.
     *
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Autenticar usuário",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login realizado com sucesso — retorna token Bearer"),
     *     @OA\Response(response=401, description="Credenciais inválidas", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Logout and revoke the current token.
     *
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Revogar token (logout)",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logout realizado com sucesso"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(['message' => 'Logged out successfully.']);
    }

    /**
     * Get the authenticated user.
     *
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Obter usuário autenticado",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Dados do usuário autenticado"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()));
    }
}
