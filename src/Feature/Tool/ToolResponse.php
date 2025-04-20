<?php

declare(strict_types=1);

namespace Croft\Feature\Tool;

class ToolResponse
{
    private array $contentBlock;

    private bool $errored = false;

    private function __construct(array $contentBlock, bool $errored = false)
    {
        $this->contentBlock = $contentBlock;
        $this->errored = $errored;
    }

    public function isError(): bool
    {
        return $this->errored;
    }

    public static function text(string $text, bool $errored = false): self
    {
        return new self(['type' => 'text', 'text' => $text], $errored);
    }

    public static function array(array $data, bool $errored = false): self
    {
        // Represent array data as JSON string within a text block
        return new self(['type' => 'text', 'text' => json_encode($data)], $errored);
    }

    public static function image(string $base64Data, string $mimeType): self
    {
        return new self([
            'type' => 'image',
            'data' => $base64Data,
            'mimeType' => $mimeType,
        ]);
    }

    public static function error(string $message): self
    {
        // Error messages are still text blocks
        return new self(['type' => 'text', 'text' => $message], true);
    }

    public function toArray(): array
    {
        return [
            'content' => [$this->contentBlock],
            'isError' => $this->errored,
        ];
    }
}
