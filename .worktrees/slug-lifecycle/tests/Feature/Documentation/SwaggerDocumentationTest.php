<?php

namespace Tests\Feature\Documentation;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_generated_swagger_documents_the_authenticated_user_endpoint_using_auth_me(): void
    {
        Artisan::call('l5-swagger:generate');

        /** @var array{paths: array<string, mixed>, components: array<string, mixed>} $spec */
        $spec = json_decode((string) file_get_contents(storage_path('api-docs/api-docs.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('/auth/me', $spec['paths']);
        $this->assertArrayNotHasKey('/auth/user', $spec['paths']);
        $this->assertSame('Sanctum API Token', $spec['components']['securitySchemes']['bearerAuth']['bearerFormat']);
        $this->assertArrayHasKey(
            'tag_ids',
            $spec['paths']['/products']['post']['requestBody']['content']['application/json']['schema']['properties'],
        );
        $this->assertArrayHasKey(
            'zip_code',
            $spec['paths']['/orders']['post']['requestBody']['content']['application/json']['schema']['properties']['shipping_address']['properties'],
        );
        $this->assertArrayHasKey(
            'zip_code',
            $spec['paths']['/orders']['post']['requestBody']['content']['application/json']['schema']['properties']['billing_address']['properties'],
        );
    }
}
