<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_json_endpoint_returns_valid_spec(): void
    {
        $response = $this->get('/openapi.json');

        $response->assertOk();
        $this->assertStringContainsString('json', $response->headers->get('Content-Type'));
    }

    public function test_openapi_spec_has_required_top_level_fields(): void
    {
        $response = $this->get('/openapi.json');
        $spec = $response->json();

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
    }

    public function test_openapi_info_contains_title_version_description(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertNotEmpty($spec['info']['title']);
        $this->assertNotEmpty($spec['info']['version']);
        $this->assertNotEmpty($spec['info']['description']);
    }

    public function test_openapi_documents_marketplace_endpoints(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertArrayHasKey('/api/marketplace', $spec['paths']);
        $this->assertArrayHasKey('get', $spec['paths']['/api/marketplace']);
        $tags = $spec['paths']['/api/marketplace']['get']['tags'] ?? [];
        $this->assertContains('marketplace', $tags);
    }

    public function test_openapi_documents_newsletter_endpoints(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertArrayHasKey('/api/newsletter/subscribe', $spec['paths']);
        $this->assertArrayHasKey('post', $spec['paths']['/api/newsletter/subscribe']);
        $this->assertArrayHasKey('/api/newsletter/unsubscribe', $spec['paths']);
    }

    public function test_openapi_documents_health_endpoints(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertArrayHasKey('/health', $spec['paths']);
        $this->assertArrayHasKey('/health/ready', $spec['paths']);
    }

    public function test_openapi_documents_import_endpoint_with_security(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertArrayHasKey('/api/import-valuation', $spec['paths']);
        $path = $spec['paths']['/api/import-valuation']['post'];

        $this->assertArrayHasKey('security', $path);
        $this->assertContains('sharedToken', array_keys($path['security'][0]));
    }

    public function test_openapi_car_schema_is_complete(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertArrayHasKey('Car', $spec['components']['schemas']);
        $schema = $spec['components']['schemas']['Car'];

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('brand', $schema['properties']);
        $this->assertArrayHasKey('model', $schema['properties']);
        $this->assertArrayHasKey('purchase_price', $schema['properties']);
        $this->assertArrayHasKey('traffic_light', $schema['properties']);
        $this->assertArrayHasKey('_links', $schema['properties']);
    }

    public function test_openapi_car_list_includes_pagination_meta(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $meta = $spec['components']['schemas']['CarList']['properties']['meta']['properties'];

        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('per_page', $meta);
        $this->assertArrayHasKey('current_page', $meta);
        $this->assertArrayHasKey('last_page', $meta);
    }

    public function test_openapi_health_schema_includes_subsystems(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $schema = $spec['components']['schemas']['HealthCheck'];
        $this->assertContains('status', array_keys($schema['properties']));
        $this->assertContains('timestamp', array_keys($schema['properties']));
        $this->assertContains('checks', array_keys($schema['properties']));
    }

    public function test_openapi_defines_security_schemes(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes']);
        $this->assertArrayHasKey('sharedToken', $spec['components']['securitySchemes']);
        $this->assertSame('http', $spec['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertSame('apiKey', $spec['components']['securitySchemes']['sharedToken']['type']);
    }

    public function test_openapi_servers_include_production_and_local(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $this->assertNotEmpty($spec['servers']);
        $this->assertGreaterThanOrEqual(2, count($spec['servers']));

        $descriptions = array_column($spec['servers'], 'description');
        $this->assertContains('Production', $descriptions);
    }

    public function test_openapi_marketplace_filters_are_documented(): void
    {
        $spec = $this->get('/openapi.json')->json();
        $params = $spec['paths']['/api/marketplace']['get']['parameters'];

        $paramNames = array_column($params, 'name');
        $this->assertContains('search', $paramNames);
        $this->assertContains('min_price', $paramNames);
        $this->assertContains('max_price', $paramNames);
        $this->assertContains('fuel', $paramNames);
    }

    public function test_openapi_responses_include_rate_limit_429(): void
    {
        $spec = $this->get('/openapi.json')->json();

        $newsletter = $spec['paths']['/api/newsletter/subscribe']['post']['responses'];
        $this->assertArrayHasKey('429', $newsletter);

        $marketplace = $spec['paths']['/api/marketplace']['get']['responses'];
        $this->assertArrayHasKey('429', $marketplace);
    }
}
