<?php

namespace KmApiDocVendor\LaravelApiDocs\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

class GenerateDocsCommand extends Command
{
    protected $signature = 'kmapidoc:generate';
    protected $description = 'Generate OpenAPI Swagger JSON from Laravel routes and controllers';

    public function handle()
    {
        $this->info('Generating API documentation...');

        $routes = Route::getRoutes();
        $paths = [];

        foreach ($routes as $route) {
            $actionName = $route->getActionName();

            if (!$actionName || $actionName === 'Closure' || !str_contains($actionName, '@')) {
                continue;
            }

            $uri = $route->uri();
            $methods = array_diff($route->methods(), ['HEAD']);
            [$controllerClass, $methodName] = explode('@', $actionName);

            try {
                $reflection = new ReflectionMethod($controllerClass, $methodName);
                $controllerShort = (new ReflectionClass($controllerClass))->getShortName();

                // Get the full doc comment from the method
                $docComment = $reflection->getDocComment();
                $methodDescription = $this->extractDescriptionFromDocComment($docComment);

                // Route path params like {id}
                $parameters = [];
                preg_match_all('/\{(.*?)\}/', $uri, $matches);
                foreach ($matches[1] as $match) {
                    $parameters[] = [
                        'name' => $match,
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ];
                }

                // Request body schema (only if FormRequest is used)
                $requestBody = [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [],
                                'required' => []
                            ]
                        ]
                    ]
                ];
                $hasRequestBody = false;

                foreach ($reflection->getParameters() as $param) {
                    $paramType = $param->getType();

                    if ($paramType && !$paramType->isBuiltin()) {
                        $paramClassName = $paramType->getName();

                        if (is_subclass_of($paramClassName, FormRequest::class)) {
                            $formRequestInstance = app($paramClassName);
                            $rules = method_exists($formRequestInstance, 'rules') ? $formRequestInstance->rules() : [];

                            foreach ($rules as $field => $rule) {
                                $ruleArray = is_array($rule) ? $rule : explode('|', $rule);
                                $isRequired = in_array('required', $ruleArray);

                                $requestBody['content']['application/json']['schema']['properties'][$field] = [
                                    'type' => $this->getOpenApiTypeFromRules($ruleArray)
                                ];

                                if ($isRequired) {
                                    $requestBody['content']['application/json']['schema']['required'][] = $field;
                                }
                            }

                            $hasRequestBody = true;

                        } elseif ($paramClassName === Request::class) {
                            // Basic request — no auto-inspect, but document as object
                            $requestBody['content']['application/json']['schema']['properties']['example_field'] = [
                                'type' => 'string'
                            ];
                            $hasRequestBody = true;
                        } else {
                            // Other typed parameter, treat as query
                            $parameters[] = [
                                'name' => $param->getName(),
                                'in' => 'query',
                                'required' => !$param->isOptional(),
                                'schema' => ['type' => 'string']
                            ];
                        }
                    } else {
                        // Untyped scalar or optional
                        $parameters[] = [
                            'name' => $param->getName(),
                            'in' => 'query',
                            'required' => !$param->isOptional(),
                            'schema' => ['type' => 'string']
                        ];
                    }
                }

                // Add each HTTP method for this route
                foreach ($methods as $httpMethod) {
                    $httpMethod = strtolower($httpMethod);

                    if (!isset($paths["/{$uri}"])) {
                        $paths["/{$uri}"] = [];
                    }

                    $operation = [
                        'summary' => "$controllerShort@$methodName", // Default fallback
                        'description' => $methodDescription,  // Full doc comment as description
                        'tags' => [$controllerShort],
                        'parameters' => $parameters,
                        'responses' => [
                            '200' => ['description' => 'Successful response'],
                            '400' => ['description' => 'Bad Request'],
                            '404' => ['description' => 'Not Found'],
                            '500' => ['description' => 'Internal Server Error'],
                        ]
                    ];

                    if ($hasRequestBody) {
                        $operation['requestBody'] = $requestBody;
                    }

                    $paths["/{$uri}"][$httpMethod] = $operation;
                }
            } catch (\Throwable $e) {
                $this->error("Error processing route {$uri}: " . $e->getMessage());
                continue;
            }
        }

        // Final Swagger Output
        $swagger = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Auto-Generated API Documentation',
                'version' => '1.0.0'
            ],
            'paths' => $paths
        ];

        file_put_contents(public_path('swagger.json'), json_encode($swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("swagger.json generated at: public/swagger.json");
    }

    // Helper to extract the full doc comment as description
    protected function extractDescriptionFromDocComment(?string $docComment): string
    {
        if (!$docComment) {
            return '';
        }

        // Remove all comment markers and split lines
        $lines = preg_split('/\R/', $docComment);
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\/\*\*?/', '', $line);  // Remove /* or /**
            $line = preg_replace('/\*\/$/', '', $line);     // Remove */
            $line = preg_replace('/^\*/', '', $line);       // Remove leading *
            $line = trim($line);
            if ($line !== '') {
                $cleanLines[] = $line;
            }
        }

        // Return the full comment as description
        return implode("\n", $cleanLines);
    }

    // Convert Laravel validation rules to OpenAPI types
    protected function getOpenApiTypeFromRules(array $rules): string
    {
        if (in_array('integer', $rules) || in_array('int', $rules)) return 'integer';
        if (in_array('boolean', $rules) || in_array('bool', $rules)) return 'boolean';
        if (in_array('array', $rules)) return 'array';
        if (in_array('numeric', $rules) || in_array('number', $rules)) return 'number';
        return 'string';
    }
}
