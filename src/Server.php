<?php

declare(strict_types=1);

namespace Croft;

use Croft\Exceptions\ProtocolException;
use Croft\Feature\Prompt\AbstractPrompt;
use Croft\Feature\Prompt\PromptRegistry;
use Croft\Feature\Resource\AbstractResource;
use Croft\Feature\Resource\AbstractResourceTemplate;
use Croft\Feature\Resource\ResourceRegistry;
use Croft\Feature\Resource\ResourceTemplateRegistry;
use Croft\Feature\Resource\UriTemplateParser;
use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolRegistry;
use Croft\Message\Notification;
use Croft\Message\Request;
use Croft\Message\Response;
use Croft\Protocol\Capability;
use Croft\Protocol\JsonRpc;
use Croft\Transport\StdioTransport;
use Croft\Transport\TransportInterface;

class Server
{
    private TransportInterface $transport;

    private ToolRegistry $toolRegistry;

    private PromptRegistry $promptRegistry;

    private ResourceRegistry $resourceRegistry;

    private ResourceTemplateRegistry $resourceTemplateRegistry;

    private Cache $cache;

    private bool $initialized = false;

    private bool $pingEnabled = false;

    private int $pingIntervalMs = 30000;

    private int $pingTimeoutMs = 3000;

    private $pingFailureCallback = null;

    private float $lastPingTime = 0.0;

    private ?string $pendingPingId = null;

    // The timestamp (in milliseconds) when the pending ping was sent
    private float $pingSentTimestamp = 0.0;

    // The server instructions to be sent during initialization
    private string $instructions = '';

    /**
     * @param  string  $name  The server name
     * @param  string  $version  The server version - 1.0.0
     * @param  bool  $debug  Whether to enable debug mode
     */
    public function __construct(private string $name = 'MCP Server', private string $version = '1.0.0', private bool $debug = false)
    {
        // Displaying any errors will break the JSON-RPC protocol
        ini_set('display_errors', '0');
        $this->toolRegistry = new ToolRegistry;
        $this->promptRegistry = new PromptRegistry;
        $this->resourceRegistry = new ResourceRegistry;
        $this->resourceTemplateRegistry = new ResourceTemplateRegistry;
        $this->transport = new StdioTransport;
        $this->cache = new Cache;
    }

    /**
     * Set the transport to use for this server
     *
     * @param  TransportInterface  $transport  The transport to use
     */
    public function setTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Register a tool with the server
     *
     * @param  string|AbstractTool  $tool  The tool to register (class name or instance)
     */
    public function tool(string|AbstractTool $tool): self
    {
        if (is_string($tool)) {
            // Handle class name case
            $tool = new $tool;
        }

        $tool->setCache($this->cache);

        $this->toolRegistry->register($tool);

        return $this;
    }

    /**
     * Register all tools from a directory
     *
     * This method scans the specified directory for PHP files,
     * loads all classes that extend AbstractTool, and registers them.
     *
     * @param  string  $directory  The directory containing tool classes
     * @param  string  $namespace  Optional namespace prefix for classes in this directory
     *
     * @throws \InvalidArgumentException If directory doesn't exist or isn't readable
     */
    public function tools(string $directory, string $namespace = ''): self
    {
        $count = $this->loadClassesFromDirectory(
            $directory,
            $namespace,
            AbstractTool::class,
            'tool',
            function ($tool) {
                $this->toolRegistry->register($tool);
                $this->log('Registered tool: '.$tool->getName());
            }
        );

        $this->log("Loaded $count tools from $directory");

        return $this;
    }

    /**
     * Register a prompt with the server
     *
     * @param  string|AbstractPrompt  $prompt  The prompt to register (class name or instance)
     */
    public function prompt(string|AbstractPrompt $prompt): self
    {
        if (is_string($prompt)) {
            // Handle class name case
            $prompt = new $prompt;
        }

        $this->promptRegistry->register($prompt);

        return $this;
    }

    /**
     * Register all prompts from a directory
     *
     * This method scans the specified directory for PHP files,
     * loads all classes that extend AbstractPrompt, and registers them.
     *
     * @param  string  $directory  The directory containing prompt classes
     * @param  string  $namespace  Optional namespace prefix for classes in this directory
     *
     * @throws \InvalidArgumentException If directory doesn't exist or isn't readable
     */
    public function prompts(string $directory, string $namespace = ''): self
    {
        $count = $this->loadClassesFromDirectory(
            $directory,
            $namespace,
            AbstractPrompt::class,
            'prompt',
            function ($prompt) {
                $this->promptRegistry->register($prompt);
                $this->log('Registered prompt: '.$prompt->getName());
            }
        );

        $this->log("Loaded $count prompts from $directory");

        return $this;
    }

    /**
     * Register a resource with the server
     *
     * @param  string|AbstractResource  $resource  The resource to register (class name or instance)
     */
    public function resource(string|AbstractResource $resource): self
    {
        if (is_string($resource)) {
            // Handle class name case
            $resource = new $resource;
        }

        $this->resourceRegistry->register($resource);

        return $this;
    }

    /**
     * Register all resources from a directory
     *
     * This method scans the specified directory for PHP files,
     * loads all classes that extend AbstractResource, and registers them.
     *
     * @param  string  $directory  The directory containing resource classes
     * @param  string  $namespace  Optional namespace prefix for classes in this directory
     *
     * @throws \InvalidArgumentException If directory doesn't exist or isn't readable
     */
    public function resources(string $directory, string $namespace = ''): self
    {
        // Load AbstractResource instances
        $resourceCount = $this->loadClassesFromDirectory(
            $directory,
            $namespace,
            AbstractResource::class,
            'resource',
            function ($resource) {
                $this->resourceRegistry->register($resource);
                $this->log('Registered resource: '.$resource->getUri());
            }
        );

        // Load AbstractResourceTemplate instances
        $templateCount = $this->loadClassesFromDirectory(
            $directory,
            $namespace,
            AbstractResourceTemplate::class,
            'resource template',
            function ($template) {
                $this->resourceTemplateRegistry->register($template);
                $this->log('Registered resource template: '.$template->getUriTemplate());
            }
        );

        $this->log("Loaded $resourceCount resources and $templateCount resource templates from $directory");

        return $this;
    }

    /**
     * Register a resource template with the server
     *
     * @param  string|AbstractResourceTemplate  $template  The template to register (class name or instance)
     */
    public function resourceTemplate(string|AbstractResourceTemplate $template): self
    {
        if (is_string($template)) {
            // Handle class name case
            $template = new $template;
        }

        $this->resourceTemplateRegistry->register($template);

        return $this;
    }

    /**
     * Set the *server* instructions that will be sent during initialization
     *
     * @param  string  $instructions  The instructions to set
     */
    public function instructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function run(): void
    {
        $this->registerSignalHandlers();
        $this->log('Signal handling enabled (Ctrl+C to exit)');

        $this->handleMessages();
    }

    /**
     * Register signal handlers for SIGINT and SIGTERM
     */
    private function registerSignalHandlers(): void
    {
        // Enable async signals for immediate handling
        pcntl_async_signals(true);

        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
    }

    /**
     * Handle signals (SIGINT, SIGTERM)
     *
     * @param  int  $signo  Signal number
     */
    public function handleSignal(int $signo): void
    {
        switch ($signo) {
            case SIGINT:
                $this->log(PHP_EOL.'SIGINT received, shutting down...');
                exit(0);
            case SIGTERM:
                $this->log(PHP_EOL.'SIGTERM received, shutting down...');
                exit(0);
        }
    }

    /**
     * Handle incoming messages
     */
    private function handleMessages(): void
    {
        $continueRunning = true;
        /** @phpstan-ignore while.alwaysTrue */
        while ($continueRunning) {
            // Dispatch any pending signals if signal handling is enabled
            pcntl_signal_dispatch();

            $currentTimeMs = microtime(true) * 1000;

            // Check if a pending ping has timed out
            if ($this->pendingPingId !== null) {
                $elapsedTimeMs = $currentTimeMs - $this->pingSentTimestamp;
                if ($elapsedTimeMs >= $this->pingTimeoutMs) {
                    $this->log("Ping timeout reached for ID: {$this->pendingPingId}");
                    $this->pendingPingId = null; // Reset pending state
                    if ($this->pingFailureCallback) {
                        ($this->pingFailureCallback)();
                        // For now we're not going to disconnect the client as some clients simply don't support responding to server pings
                        // Assume callback might signal shutdown, respect it
                        // $continueRunning = false; // Stop the loop on timeout if callback exists
                        // continue; // Skip further processing in this iteration
                    }
                }
            }

            // Check if we should send a new ping (only if enabled, initialized, interval passed, and no ping is currently pending)
            if ($this->pingEnabled && $this->initialized && $this->pendingPingId === null) {
                if ($currentTimeMs - $this->lastPingTime >= $this->pingIntervalMs) {
                    // It's time to send a ping
                    $this->ping(); // Send the non-blocking ping
                    // lastPingTime is updated inside ping() when sent
                }
            }

            $rawMessage = $this->transport->read();

            if ($rawMessage === null) {
                // No message available, sleep a bit to prevent CPU spin
                usleep(100000);

                continue;
            }

            $this->log("Received message: {$rawMessage}");
            $this->debug($rawMessage);

            try {
                $message = JsonRpc::parse($rawMessage);

                if ($message instanceof Request) {
                    $this->handleRequest($message);
                } elseif ($message instanceof Notification) {
                    $this->handleNotification($message);
                } elseif ($message instanceof Response) {
                    // Check if this is the pong we are waiting for
                    if ($this->pendingPingId !== null && $message->getId() === $this->pendingPingId) {
                        $this->log("Received ping response (ID: {$this->pendingPingId})");
                        $this->pendingPingId = null; // Pong received, clear pending state
                        // Do not pass this response to handleResponse, it's been handled.
                    } else {
                        // Handle other unexpected or non-ping responses
                        $this->handleResponse($message);
                    }
                }
            } catch (ProtocolException $e) {
                // Handle protocol errors
                $errorResponse = JsonRpc::error(
                    'unknown', // We don't know the ID if parsing failed
                    $e->getCode() ?: JsonRpc::PARSE_ERROR,
                    $e->getMessage()
                );
                $this->transport->write(JsonRpc::stringify($errorResponse));
                $this->debug(JsonRpc::stringify($errorResponse));
            } catch (\Exception $e) {
                // Handle unexpected errors
                $errorResponse = JsonRpc::error(
                    'unknown',
                    JsonRpc::INTERNAL_ERROR,
                    'Internal server error: '.$e->getMessage()
                );
                $this->transport->write(JsonRpc::stringify($errorResponse));
                $this->debug(JsonRpc::stringify($errorResponse));
            }
        }
    }

    private function debug(string $message): void
    {
        if ($this->debug) {
            fwrite(STDERR, $message.PHP_EOL);
        }
    }

    /**
     * Handle request based on method
     *
     * @param  Request  $request  The request to handle
     *
     * @throws ProtocolException If the method is not supported or an error occurs
     */
    private function handleRequest(Request $request): void
    {
        $method = $request->getMethod();
        $id = $request->getId();
        $params = $request->getParams() ?? [];

        // Log for debugging
        $this->log("Received request: '{$method}' (ID: {$id})");

        try {
            // Check if we're initialized
            if (! $this->initialized && $method !== 'initialize' && $method !== 'ping') {
                throw new ProtocolException(
                    'Server not initialized. Call initialize first.',
                    JsonRpc::INVALID_REQUEST
                );
            }

            $response = match ($method) {
                'initialize' => $this->handleInitialize($id, $params),
                'ping' => $this->handlePing($id, $params),
                'tools/list' => $this->handleToolsList($id, $params),
                'tools/call' => $this->handleToolCall($id, $params),
                'prompts/list' => $this->handlePromptsList($id, $params),
                'prompts/get' => $this->handlePromptsGet($id, $params),
                'resources/list' => $this->handleResourcesList($id, $params),
                'resources/read' => $this->handleResourcesRead($id, $params),
                'resources/templates/list' => $this->handleResourcesTemplatesList($id, $params),
                default => throw new ProtocolException(
                    "Method not found: {$method}",
                    JsonRpc::METHOD_NOT_FOUND
                )
            };

            $this->log('Sent response: '.json_encode($response));
            $this->transport->write(JsonRpc::stringify($response));
            $this->debug(JsonRpc::stringify($response));
        } catch (ProtocolException $e) {
            $response = Response::error($id, $e->getCode(), $e->getMessage());
            $this->log('Sent response: '.json_encode($response).' with error: '.$e->getMessage());
            $this->transport->write(JsonRpc::stringify($response));
            $this->debug(JsonRpc::stringify($response));
        } catch (\Exception $e) {
            $response = Response::error($id, JsonRpc::INTERNAL_ERROR, "Internal server error: {$e->getMessage()}");
            $this->transport->write(JsonRpc::stringify($response));
            $this->debug(JsonRpc::stringify($response));
        }
    }

    /**
     * Handle a response message
     *
     * @param  Response  $response  The response to handle
     */
    private function handleResponse(Response $response): void
    {
        // Not sure we should get here
        $id = $response->getId();
        $result = $response->getResult();

        $this->log("Received response: {$id} with result: ".json_encode($result));
    }

    /**
     * Handle a notification message
     *
     * @param  Notification  $notification  The notification to handle
     */
    private function handleNotification(Notification $notification): void
    {
        $method = $notification->getMethod();

        // Log for debugging
        // $this->log("Received notification: {$method}");

        if ($method === 'notifications/initialized') {
            // $this->log('Client initialized');
            // Could trigger additional setup here if needed
        }

        // No response for notifications
    }

    /**
     * Handle the initialize request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     */
    private function handleInitialize(string|int $id, array $params): Response
    {
        $instructions = $this->instructions;
        $this->log('Received initialization request');

        // Extract client capabilities from params
        $clientCapabilities = $params['capabilities'] ?? [];

        // Use Capability class to negotiate server capabilities based on client capabilities
        $serverCapabilities = Capability::negotiateCapabilities(
            $clientCapabilities,
            $this->toolRegistry,
            $this->promptRegistry,
            $this->resourceRegistry
        );

        // Use the Capability class to format the response
        $result = Capability::createInitializeResponse(
            $this->name,
            $this->version,
            $serverCapabilities,
            $instructions
        );

        // Use the client's requested protocol version if available
        if (isset($params['protocolVersion'])) {
            $result['protocolVersion'] = $params['protocolVersion'];
        }

        $this->initialized = true;
        $this->log('Sending initialization response: '.json_encode($result));

        return Response::result($id, $result);
    }

    private function handlePing(string|int $id, array $params): Response
    {
        return Response::pong($id);
    }

    /**
     * Handle a tool call request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     */
    private function handleToolCall(string|int $id, array $params): Response
    {
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! $toolName) {
            throw new ProtocolException('Missing tool name', JsonRpc::INVALID_PARAMS);
        }

        $tool = $this->toolRegistry->getItem($toolName);
        if (! $tool) {
            $this->log("Unknown tool: '{$toolName}' not found in registry");
            throw new ProtocolException("Unknown tool: {$toolName}", JsonRpc::METHOD_NOT_FOUND);
        }

        // Get tool annotations
        $annotations = $tool->getAnnotations();

        // TODO: Check rate limits if configured
        // if (isset($annotations['rateLimit'])) {
        // $this->checkRateLimit($toolName, $annotations['rateLimit']);
        // }

        // Validate arguments against schema
        // In a full implementation, we would validate against JSON Schema here

        try {
            /*
            // TODO: Execute the tool with timeout if specified
                if (isset($annotations['timeout']) && $annotations['timeout'] > 0) {
                    // Set timeout handler
                    $previousTimeLimit = ini_get('max_execution_time');
                    set_time_limit($annotations['timeout'] / 1000);
                }
            */

            $result = $tool->handle($arguments);

            // TODO: Restore previous timeout
            // if (isset($previousTimeLimit)) {
            //     set_time_limit($previousTimeLimit);
            // }

            return new Response($id, $result->toArray());
        } catch (\Exception $e) {
            // Return a successful response with isError flag
            return new Response($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Error executing tool: '.$e->getMessage(),
                    ],
                ],
                'isError' => true,
            ]);
        }
    }

    /**
     * Handle the tools/list request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     */
    private function handleToolsList(string|int $id, array $params): Response
    {
        $toolSchemas = $this->toolRegistry->getSchemas();

        return new Response($id, [
            'tools' => $toolSchemas,
            // Don't include nextCursor at all if there's no pagination
            // 'nextCursor' => null // No pagination for now
        ]);
    }

    /**
     * Handle the prompts/list request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     */
    private function handlePromptsList(string|int $id, array $params): Response
    {
        // Pagination would be implemented here, but we'll keep it simple for now
        $promptSchemas = $this->promptRegistry->getSchemas();

        return new Response($id, [
            'prompts' => $promptSchemas,
            // Don't include nextCursor at all if there's no pagination
            // 'nextCursor' => null // No pagination for now
        ]);
    }

    /**
     * Handle the prompts/get request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     *
     * @throws ProtocolException If the prompt name is missing or the prompt doesn't exist
     */
    private function handlePromptsGet(string|int $id, array $params): Response
    {
        $promptName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! $promptName) {
            throw new ProtocolException('Missing prompt name', JsonRpc::INVALID_REQUEST);
        }

        try {
            $prompt = $this->promptRegistry->getItem($promptName);

            return new Response($id, $prompt->getResponse($arguments)->toArray());
        } catch (ProtocolException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ProtocolException(
                'Error rendering prompt: '.$e->getMessage(),
                JsonRpc::INTERNAL_ERROR
            );
        }
    }

    /**
     * Handle resources/list request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     */
    private function handleResourcesList(string|int $id, array $params): Response
    {
        $result = [
            'resources' => $this->resourceRegistry->getSchemas(),
        ];

        return Response::result($id, $result);
    }

    /**
     * Handle resources/read request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     *
     * @throws ProtocolException If the requested resource does not exist
     */
    private function handleResourcesRead(string|int $id, array $params): Response
    {
        $uri = $params['uri'] ?? null;

        if (! $uri) {
            throw new ProtocolException('Missing required parameter: uri', JsonRpc::INVALID_PARAMS);
        }

        // First check if this URI directly matches a registered resource
        if ($this->resourceRegistry->hasItem($uri)) {
            $this->log("Resource found: {$uri}");
            $resource = $this->resourceRegistry->get($uri);

            return new Response($id, $resource->getResponse()->toArray());
        }

        // If not a direct match, try to match against templates
        $matchedTemplate = $this->matchUriAgainstTemplates($uri);

        if ($matchedTemplate !== null) {
            [$template, $templateParams] = $matchedTemplate;

            // Let the template create the appropriate resource
            $resource = $template->createResource($uri, $templateParams);
            $content = $resource->getContent();

            return Response::result($id, [
                'contents' => [$content],
            ]);
        }

        // If we get here, no resource or template matched
        throw new ProtocolException("Resource not found: {$uri}", JsonRpc::RESOURCE_NOT_FOUND);
    }

    /**
     * Match a URI against registered templates
     *
     * @param  string  $uri  The URI to match
     * @return array|null [AbstractResourceTemplate, array $params] or null if no match
     */
    private function matchUriAgainstTemplates(string $uri): ?array
    {
        $parser = new UriTemplateParser;

        // Try all registered templates in order
        foreach ($this->resourceTemplateRegistry->getItems() as $template) {
            $uriTemplate = $template->getUriTemplate();
            $params = $parser->match($uri, $uriTemplate);

            if ($params !== null) {
                // We found a matching template and extracted its parameters
                return [$template, $params];
            }
        }

        return null;
    }

    /**
     * Handle resources/templates/list request
     *
     * @param  string|int  $id  The request ID
     * @param  array  $params  The request parameters
     * @return Response The response
     */
    private function handleResourcesTemplatesList(string|int $id, array $params): Response
    {
        $templates = $this->resourceTemplateRegistry->getSchemas();

        $result = [
            'resourceTemplates' => $templates,
        ];

        return Response::result($id, $result);
    }

    /**
     * Send a ping request to the client (non-blocking)
     *
     * This method sends the ping and updates the server state to expect a response.
     * The actual response handling and timeout detection happens in the main message loop.
     */
    public function ping(): void
    {
        $this->log('Ping requested');

        // Avoid sending a new ping if one is already pending
        if ($this->pendingPingId !== null) {
            $this->log("Ping skipped: Already waiting for response to ping ID {$this->pendingPingId}");

            return;
        }

        // Generate a unique ID for this ping
        $pingId = 'ping-'.uniqid();

        // Create ping request
        $pingRequest = new Request($pingId, 'ping');

        // Send the ping
        $this->log("Sending ping request (ID: {$pingId})");
        if ($this->transport->write(JsonRpc::stringify($pingRequest))) {
            // Update state only if write was successful (optional, assumes write throws on error)
            $this->pendingPingId = $pingId;
            $this->pingSentTimestamp = microtime(true) * 1000;
            $this->lastPingTime = $this->pingSentTimestamp; // Record time ping was sent for interval calculation
        } else {
            $this->log("Failed to send ping request (ID: {$pingId})");
            // Optionally trigger failure callback immediately if write fails?
            // if ($this->pingFailureCallback) {
            //     ($this->pingFailureCallback)();
            // }
        }

        // No waiting or reading here - return immediately
    }

    /**
     * Configure periodic ping to keep the connection alive
     *
     * @param  bool  $enabled  Whether to enable periodic pings
     * @param  int  $intervalMs  Interval between pings in milliseconds
     * @param  int  $timeoutMs  Timeout for each ping in milliseconds
     * @param  callable|null  $failureCallback  Callback to call when a ping fails
     */
    public function configurePing(
        bool $enabled = true,
        int $intervalMs = 30000,
        int $timeoutMs = 3000,
        ?callable $failureCallback = null
    ): self {
        $this->pingEnabled = $enabled;
        $this->pingIntervalMs = $intervalMs;
        $this->pingTimeoutMs = $timeoutMs;
        $this->pingFailureCallback = $failureCallback ?? function () {
            $this->log('Ping failed, but no failure callback configured');
        };

        $this->lastPingTime = microtime(true) * 1000;
        // Reset pending state when reconfiguring
        $this->pendingPingId = null;
        $this->pingSentTimestamp = 0.0;

        return $this;
    }

    /**
     * Log a message for debugging
     *
     * @param  string  $message  The message to log
     */
    private function log(string $message): void
    {
        if ($this->debug) {
            fwrite(STDERR, sprintf('[%s] %s', date('Y-m-d H:i:s'), $message).PHP_EOL);
        }
    }

    /**
     * Load and register classes from a directory
     *
     * @param  string  $directory  Directory to load files from
     * @param  string  $namespace  Namespace prefix for loaded classes
     * @param  string  $baseClass  Base class that loaded classes should extend
     * @param  string  $type  Type of items being loaded (for logging)
     * @param  callable  $registerCallback  Callback to register the loaded class instance
     * @return int Number of loaded classes
     */
    private function loadClassesFromDirectory(
        string $directory,
        string $namespace,
        string $baseClass,
        string $type,
        callable $registerCallback
    ): int {
        if (! is_dir($directory) || ! is_readable($directory)) {
            throw new \InvalidArgumentException("Directory '$directory' doesn't exist or isn't readable");
        }

        $this->log("Loading {$type}s from directory: $directory");

        // Get all PHP files in the directory
        $phpFiles = glob($directory.'/*.php');

        if (empty($phpFiles)) {
            $this->log("No PHP files found in $directory");

            return 0;
        }

        $count = 0;

        foreach ($phpFiles as $file) {
            // Get the filename without extension to use as class name
            $className = pathinfo($file, PATHINFO_FILENAME);

            // Build fully qualified class name with namespace if provided
            $fqcn = $namespace ? $namespace.'\\'.$className : $className;

            try {
                // Check if the file contains a class
                if (! class_exists($fqcn, false)) {
                    // Try to include the file to load the class
                    require_once $file;

                    // Check again if class exists after including file
                    if (! class_exists($fqcn)) {
                        $this->log("Warning: Could not find class '$fqcn' in file '$file'");

                        continue;
                    }
                }

                // Check if class is an instance of the base class
                $reflection = new \ReflectionClass($fqcn);
                if (! $reflection->isInstantiable() || ! $reflection->isSubclassOf($baseClass)) {
                    continue;
                }

                // Check if the class has a constructor that requires parameters
                $constructor = $reflection->getConstructor();
                if ($constructor !== null) {
                    $params = $constructor->getParameters();
                    $hasRequiredParams = false;
                    foreach ($params as $param) {
                        if (! $param->isOptional()) {
                            $hasRequiredParams = true;
                            break;
                        }
                    }

                    if ($hasRequiredParams) {
                        $this->log("Skipping {$type} '$fqcn' as it requires constructor parameters");

                        continue;
                    }
                }

                // Create instance
                $instance = new $fqcn;

                // Register using the callback
                $registerCallback($instance);
                $count++;
            } catch (\Throwable $e) {
                $this->log("Error loading {$type} from '$file': ".$e->getMessage());
                // Continue to next file rather than letting one error stop everything
            }
        }

        return $count;
    }
}
