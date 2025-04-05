<?php

declare(strict_types=1);

namespace Croft\Message;

class Request extends Message implements \JsonSerializable
{
    public function __construct(
        protected readonly string $method,
        protected readonly string|int $id,
        protected readonly ?array $params = null,
    ) {
        parent::__construct();
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['id'] = $this->id;
        $data['method'] = $this->method;

        if ($this->params !== null) {
            $data['params'] = $this->params;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        self::validateJsonRpcVersion($data['jsonrpc']);

        if (! isset($data['method'])) {
            throw new \InvalidArgumentException('Missing method');
        }

        if (! isset($data['id'])) {
            throw new \InvalidArgumentException('Missing id');
        }

        return new self(
            method: $data['method'],
            id: $data['id'],
            params: $data['params'] ?? null,
        );
    }
}
