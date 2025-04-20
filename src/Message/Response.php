<?php

declare(strict_types=1);

namespace Croft\Message;

class Response extends Message implements \JsonSerializable
{
    public function __construct(
        protected readonly string|int $id,
        protected readonly mixed $result = null,
        protected readonly ?array $error = null,
    ) {
        if ($result !== null && $error !== null) {
            throw new \InvalidArgumentException('Response cannot have both result and error');
        }

        parent::__construct();
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getError(): ?array
    {
        return $this->error;
    }

    public function isError(): bool
    {
        return $this->error !== null;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['id'] = $this->id;

        if ($this->result !== null) {
            $data['result'] = (object) $this->result;
        }

        if ($this->error !== null) {
            $data['error'] = $this->error;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        self::validateJsonRpcVersion($data['jsonrpc']);

        if (! isset($data['id'])) {
            throw new \InvalidArgumentException('Missing id');
        }

        if (! array_key_exists('result', $data) && ! array_key_exists('error', $data)) {
            throw new \InvalidArgumentException('Missing result or error');
        }

        return new self(
            id: $data['id'],
            result: array_key_exists('result', $data) ? (object) $data['result'] : null,
            error: array_key_exists('error', $data) ? $data['error'] : null,
        );
    }

    public static function error(string|int $id, int $code, string $message, mixed $data = null): self
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $error['data'] = $data;
        }

        return new self(id: $id, error: $error);
    }

    /**
     * Create a success response with a result
     *
     * @param  string|int  $id  The request ID
     * @param  mixed  $result  The result data
     * @return self The response
     */
    public static function result(string|int $id, mixed $result): self
    {
        return new self(id: $id, result: $result);
    }

    public static function pong(string|int $id): self
    {
        return new self(id: $id, result: []);
    }
}
