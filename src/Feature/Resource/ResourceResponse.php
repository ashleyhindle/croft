<?php

declare(strict_types=1);

namespace Croft\Feature\Resource;

class ResourceResponse
{
    private array $contentBlock;

    private function __construct(array $contentBlock)
    {
        $this->contentBlock = $contentBlock;
    }

    public static function text(AbstractResource $resource): self
    {
        return new self(array_filter([
            'uri' => $resource->getUri(),
            'text' => $resource->getContent(),
            'mimeType' => $resource->getMimeType() ?? null,
        ]));
    }

    public function toArray(): array
    {
        return [
            'contents' => [$this->contentBlock],
        ];
    }
}
