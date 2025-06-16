<?php

declare(strict_types=1);

namespace Croft\Feature\Prompt;

class PromptResponse
{
    private array $contentBlock;

    private function __construct(array $contentBlock)
    {
        $this->contentBlock = $contentBlock;
    }

    public static function text(AbstractPrompt $prompt): self
    {
        return new self(array_filter([
            'description' => $prompt->getDescription(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => $prompt->getMessage($prompt->arguments()),
                    ],
                ],
            ],
        ]));
    }

    public function toArray(): array
    {
        return $this->contentBlock;
    }
}
