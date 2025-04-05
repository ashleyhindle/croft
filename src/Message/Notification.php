<?php

declare(strict_types=1);

namespace Croft\Message;

class Notification extends Message implements \JsonSerializable
{
    public function __construct(
        protected readonly string $method,
        protected readonly ?array $params = null,
    ) {
        parent::__construct();
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

        if (isset($data['id'])) {
            throw new \InvalidArgumentException('Notification cannot have an id');
        }

        return new self(
            method: $data['method'],
            params: $data['params'] ?? null,
        );
    }
}
