<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="E-commerce API",
 *     version="1.0.0",
 *     description="API REST completa do sistema de e-commerce. Todos os endpoints requerem autenticação via Bearer token (Sanctum), exceto os de autenticação pública.",
 *     @OA\Contact(email="admin@ecommerce.com")
 * )
 *
 * @OA\Server(url="/api/v1", description="API v1")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Mensagem de erro"),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="last_page", type="integer", example=7)
 * )
 */
abstract class Controller
{
    //
}
