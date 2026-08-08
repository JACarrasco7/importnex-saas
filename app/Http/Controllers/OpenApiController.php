<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * OpenAPI 3.0 specification auto-generated for ImportnexCore public API.
 *
 * Documents:
 * - GET /api/cars (marketplace listing)
 * - GET /api/cars/{id} (single car)
 * - GET /api/marketplace (public marketplace with filters)
 * - POST /api/newsletter/subscribe
 * - POST /api/import-valuation (chat report upload)
 * - GET /health (uptime monitoring)
 *
 * Visit /openapi.json to get machine-readable spec.
 * Visit /docs for interactive Swagger UI (HTML).
 */
class OpenApiController extends Controller
{
    /**
     * Return the OpenAPI 3.0 spec as JSON.
     */
    public function json(): JsonResponse
    {
        return response()->json($this->spec())
            ->header('Content-Type', 'application/json');
    }

    /**
     * Build the spec.
     *
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name', 'JJ Import Motors API'),
                'version' => '1.0.0',
                'description' => 'Public API for marketplace browsing, newsletter, and chat-report import.',
                'contact' => [
                    'name' => 'JJ Import Motors',
                    'url' => config('app.url'),
                ],
                'license' => [
                    'name' => 'Proprietary',
                ],
            ],
            'servers' => [
                ['url' => config('app.url'), 'description' => 'Production'],
                ['url' => 'http://localhost:8000', 'description' => 'Local dev'],
            ],
            'tags' => [
                ['name' => 'marketplace', 'description' => 'Public marketplace browsing'],
                ['name' => 'cars', 'description' => 'Car CRUD endpoints'],
                ['name' => 'newsletter', 'description' => 'Newsletter subscription'],
                ['name' => 'imports', 'description' => 'Chat report import'],
                ['name' => 'health', 'description' => 'Uptime monitoring'],
            ],
            'paths' => [
                '/api/marketplace' => [
                    'get' => $this->marketplaceIndexDoc(),
                ],
                '/api/marketplace/{id}' => [
                    'get' => $this->marketplaceShowDoc(),
                ],
                '/api/cars/{id}' => [
                    'get' => $this->carShowDoc(),
                ],
                '/api/newsletter/subscribe' => [
                    'post' => $this->newsletterSubscribeDoc(),
                ],
                '/api/newsletter/unsubscribe' => [
                    'delete' => $this->newsletterUnsubscribeDoc(),
                ],
                '/api/import-valuation' => [
                    'post' => $this->importValuationDoc(),
                ],
                '/health' => [
                    'get' => $this->healthDoc(),
                ],
                '/health/ready' => [
                    'get' => $this->healthReadyDoc(),
                ],
            ],
            'components' => [
                'schemas' => [
                    'Car' => $this->carSchema(),
                    'CarList' => $this->carListSchema(),
                    'HealthCheck' => $this->healthCheckSchema(),
                    'Error' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'Validation failed'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                        ],
                    ],
                ],
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                    'sharedToken' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-Import-Token',
                        'description' => 'Shared secret for chat-report import endpoints.',
                    ],
                ],
            ],
            'security' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function marketplaceIndexDoc(): array
    {
        return [
            'tags' => ['marketplace'],
            'summary' => 'List public marketplace cars',
            'description' => 'Browse the public marketplace. Supports filtering by brand, price, fuel, etc.',
            'parameters' => [
                ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Search brand or model'],
                ['name' => 'brand', 'in' => 'query', 'schema' => ['type' => 'string']],
                ['name' => 'min_price', 'in' => 'query', 'schema' => ['type' => 'number']],
                ['name' => 'max_price', 'in' => 'query', 'schema' => ['type' => 'number']],
                ['name' => 'fuel', 'in' => 'query', 'schema' => ['type' => 'string']],
                ['name' => 'transmission', 'in' => 'query', 'schema' => ['type' => 'string']],
                ['name' => 'verdict', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['Buy', 'Buy if price drops', 'Pass', 'Avoid']]],
                ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Paginated list of marketplace cars',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/CarList']]],
                ],
                '429' => ['description' => 'Rate limit exceeded'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function marketplaceShowDoc(): array
    {
        return [
            'tags' => ['marketplace'],
            'summary' => 'Get single marketplace car',
            'parameters' => [
                ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Car details',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Car']]],
                ],
                '404' => [
                    'description' => 'Car not found or not public',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function carShowDoc(): array
    {
        return [
            'tags' => ['cars'],
            'summary' => 'Get car by ID (full data)',
            'description' => 'Authenticated endpoint. Returns full car data including private fields.',
            'security' => [['bearerAuth' => []]],
            'parameters' => [
                ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Car details',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Car']]],
                ],
                '401' => ['description' => 'Unauthenticated'],
                '403' => ['description' => 'Car belongs to another organization'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function newsletterSubscribeDoc(): array
    {
        return [
            'tags' => ['newsletter'],
            'summary' => 'Subscribe to newsletter',
            'requestBody' => [
                'required' => true,
                'content' => ['application/json' => ['schema' => [
                    'type' => 'object',
                    'required' => ['email'],
                    'properties' => [
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'locale' => ['type' => 'string', 'enum' => ['es', 'en'], 'default' => 'es'],
                        'source' => ['type' => 'string', 'default' => 'web'],
                    ],
                ]]],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Subscribed',
                    'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => [
                        'success' => ['type' => 'boolean'],
                        'message' => ['type' => 'string'],
                    ]]]],
                ],
                '422' => ['description' => 'Invalid email'],
                '429' => ['description' => 'Rate limit: max 10/min per IP'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function newsletterUnsubscribeDoc(): array
    {
        return [
            'tags' => ['newsletter'],
            'summary' => 'Unsubscribe from newsletter',
            'requestBody' => [
                'required' => true,
                'content' => ['application/json' => ['schema' => [
                    'type' => 'object',
                    'required' => ['email'],
                    'properties' => ['email' => ['type' => 'string', 'format' => 'email']],
                ]]],
            ],
            'responses' => [
                '200' => ['description' => 'Unsubscribed'],
                '404' => ['description' => 'Email not found in subscriber list'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function importValuationDoc(): array
    {
        return [
            'tags' => ['imports'],
            'summary' => 'Upload chat valuation report (JSON or ZIP)',
            'description' => 'Endpoint used by the chat to import valuation reports into the system. Requires shared token.',
            'security' => [['sharedToken' => []]],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => ['schema' => ['type' => 'object', 'description' => 'Raw chat report JSON']],
                    'application/zip' => ['schema' => ['type' => 'string', 'format' => 'binary', 'description' => 'ZIP package with informe.json + fotos/']],
                ],
            ],
            'responses' => [
                '201' => [
                    'description' => 'Car created/updated',
                    'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => [
                        'car_id' => ['type' => 'integer'],
                        'redirect' => ['type' => 'string'],
                    ]]]],
                ],
                '401' => ['description' => 'Invalid or missing token'],
                '422' => ['description' => 'Invalid JSON schema'],
                '429' => ['description' => 'Rate limit exceeded'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function healthDoc(): array
    {
        return [
            'tags' => ['health'],
            'summary' => 'Full health report',
            'description' => 'Returns 200 if all subsystems are healthy, 503 if any is degraded.',
            'responses' => [
                '200' => [
                    'description' => 'Healthy',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/HealthCheck']]],
                ],
                '503' => [
                    'description' => 'Degraded',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/HealthCheck']]],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function healthReadyDoc(): array
    {
        return [
            'tags' => ['health'],
            'summary' => 'Readiness probe',
            'description' => 'Used by Kubernetes/load balancers to decide if traffic should route here.',
            'responses' => [
                '200' => ['description' => 'Ready'],
                '503' => ['description' => 'Not ready'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function carSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'brand' => ['type' => 'string', 'example' => 'Audi'],
                'model' => ['type' => 'string', 'example' => 'A3'],
                'version' => ['type' => 'string', 'nullable' => true],
                'year' => ['type' => 'string', 'example' => '06/2020'],
                'mileage' => ['type' => 'integer', 'nullable' => true],
                'purchase_price' => ['type' => 'number', 'format' => 'float'],
                'currency' => ['type' => 'string', 'example' => 'EUR'],
                'fuel' => ['type' => 'string'],
                'transmission' => ['type' => 'string'],
                'cv' => ['type' => 'integer', 'nullable' => true],
                'displacement' => ['type' => 'integer', 'nullable' => true],
                'co2' => ['type' => 'integer', 'nullable' => true],
                'consumption' => ['type' => 'number', 'nullable' => true],
                'doors' => ['type' => 'integer', 'nullable' => true],
                'seats' => ['type' => 'integer', 'nullable' => true],
                'color' => ['type' => 'string', 'nullable' => true],
                'owners' => ['type' => 'integer', 'nullable' => true],
                'status' => ['type' => 'string', 'example' => 'Located'],
                'traffic_light' => ['type' => 'string', 'enum' => ['green', 'amber', 'red', 'neutral']],
                'is_marketplace' => ['type' => 'boolean'],
                'verdict' => ['type' => 'string', 'nullable' => true, 'enum' => ['Buy', 'Buy if price drops', 'Pass', 'Avoid', null]],
                'verdict_confidence' => ['type' => 'string', 'nullable' => true, 'enum' => ['high', 'medium', 'low', null]],
                'verdict_reasoning' => ['type' => 'string', 'nullable' => true],
                'market_avg' => ['type' => 'number', 'nullable' => true],
                'market_min' => ['type' => 'number', 'nullable' => true],
                'market_max' => ['type' => 'number', 'nullable' => true],
                'estimated_saving' => ['type' => 'number', 'nullable' => true],
                'photos' => [
                    'type' => 'array',
                    'nullable' => true,
                    'items' => ['type' => 'object', 'properties' => [
                        'id' => ['type' => 'integer'],
                        'url' => ['type' => 'string'],
                        'photo_type' => ['type' => 'string'],
                        'is_primary' => ['type' => 'boolean'],
                        'order' => ['type' => 'integer'],
                    ]],
                ],
                'client' => [
                    'type' => 'object',
                    'nullable' => true,
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                    ],
                ],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                'verdict_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                '_links' => [
                    'type' => 'object',
                    'properties' => [
                        'self' => ['type' => 'string', 'format' => 'uri'],
                        'web' => ['type' => 'string', 'format' => 'uri'],
                        'admin' => ['type' => 'string', 'format' => 'uri'],
                    ],
                ],
            ],
            'required' => ['id', 'brand', 'model', 'purchase_price', 'status', 'traffic_light'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function carListSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/Car'],
                ],
                'meta' => [
                    'type' => 'object',
                    'properties' => [
                        'total' => ['type' => 'integer'],
                        'per_page' => ['type' => 'integer'],
                        'current_page' => ['type' => 'integer'],
                        'last_page' => ['type' => 'integer'],
                        'from' => ['type' => 'integer', 'nullable' => true],
                        'to' => ['type' => 'integer', 'nullable' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function healthCheckSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['healthy', 'degraded', 'ready', 'not_ready', 'alive']],
                'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                'app' => ['type' => 'string'],
                'env' => ['type' => 'string'],
                'version' => ['type' => 'string'],
                'checks' => [
                    'type' => 'object',
                    'properties' => [
                        'database' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => ['type' => 'string', 'enum' => ['ok', 'error']],
                                'driver' => ['type' => 'string'],
                                'latency_ms' => ['type' => 'number'],
                            ],
                        ],
                        'cache' => ['type' => 'object'],
                        'storage' => ['type' => 'object'],
                        'queue' => ['type' => 'object'],
                    ],
                ],
            ],
        ];
    }
}
