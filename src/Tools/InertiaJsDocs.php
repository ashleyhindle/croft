<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;

class InertiaJsDocs extends AbstractTool
{
    protected array $codeExampleFilter = [
        'laravel',
        'vue',
        'react',
        'svelte4',
        'svelte5',
    ];

    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Inertia.js Documentation')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'inertiajs_docs';
    }

    public function getDescription(): string
    {
        return 'Get the Inertia.js documentation, with working code examples you must use to write the user\'s code. It is critical you search the documentation for the exact prop names and values as seen in the code examples when you need to work with Inertia code on the backend or frontend.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'search_queries' => [
                    'type' => 'array',
                    'description' => 'Returns files from the Inertia.js documentation that match on the search queries.',
                    'items' => [
                        'type' => 'string',
                        'description' => 'The search query to search the Inertia.js documentation for.',
                    ],
                ],
                'code_example_languages' => [
                    'type' => 'array',
                    'description' => 'The languages to return code examples for. Recommend to always include "laravel" and whichever frontend framework you are using: react, vue, or svelte.',
                    'items' => [
                        'type' => 'string',
                        'description' => 'The language to include in the code examples.',
                    ],
                ],
            ],
            'required' => ['search_queries'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $this->codeExampleFilter = ! empty($arguments['code_example_languages']) ? $arguments['code_example_languages'] : $this->codeExampleFilter;

        $path = realpath(__DIR__.'/../../docs/inertiajs/2.0.6');
        $files = glob($path.'/*.jsx');
        $files = array_map(function ($file) {
            return basename($file);
        }, $files);

        $results = [];

        foreach ($files as $file) {
            $contents = file_get_contents($path.'/'.$file);
            foreach ($arguments['search_queries'] as $searchQuery) {
                if (stripos($contents, $searchQuery) !== false) {
                    $results[] = [
                        'file' => $file,
                        'documentation' => $contents,
                        'code_examples' => [],
                        'notes' => 'You MUST only use information from the code examples and documentation here to write the user\'s code. You MUST use the props as seen in the documentation and code examples.',
                    ];
                }
            }
        }

        // Now we want to get the code examples from the 'TabbedCode' components in the docs

        $results = $this->addCodeExamples($results);

        return ToolResponse::array($results);
    }

    private function addCodeExamples(array $results): array
    {
        foreach ($results as $key => $result) {
            $results[$key]['code_examples'] = $this->getCodeExamples($result['documentation']);
        }

        return $results;
    }

    private function getCodeExamples(string $contents): array
    {
        $return = [];

        // Find all TabbedCode blocks with their examples
        preg_match_all('/<TabbedCode\s+examples=\{(\[[\s\S]*?\])\}/s', $contents, $matches);

        foreach ($matches[1] as $examplesJsArray) {
            preg_match_all('/{\n\s+name: \'(?P<name>[^"]+?)\',?\n\s+language: \'(?P<language>[^"]+?)\',?\n\s+code: dedent`(?P<code>.+?)`,?\n\s+}/si', $examplesJsArray, $exampleMatches);

            $examples = [];
            foreach ($exampleMatches['name'] as $index => $name) {
                $sname = strtolower($name);
                if (! isset($examples[$sname])) {
                    $examples[$sname] = [];
                }

                $examples[$sname][] = [
                    'name' => $name,
                    'language' => $exampleMatches['language'][$index],
                    'code' => $exampleMatches['code'][$index],
                ];
            }

            // Organize examples by language
            foreach ($examples as $sname => $examples) {
                foreach ($examples as $example) {
                    if (! isset($example['name']) || ! isset($example['code'])) {
                        continue;
                    }

                    // Only include languages that were requested in codeExampleFilter
                    if (! in_array($sname, array_map('strtolower', $this->codeExampleFilter))) {
                        continue;
                    }

                    if (! isset($return[$sname])) {
                        $return[$sname] = [];
                    }

                    $return[$sname][] = $this->dedent($example['code']);
                }
            }
        }

        return $return;
    }

    /**
     * Removes common leading whitespace from every line in a string.
     *
     * It calculates the minimum indentation (shortest leading whitespace)
     * of all non-empty lines and removes that specific prefix from each line.
     * Relative indentation within the block is preserved. Handles both spaces and tabs.
     *
     * @param  string  $code  The block of code (or text) to dedent.
     * @return string The dedented code block.
     */
    private function dedent(string $code): string
    {
        // 1. Split the code into lines, keeping newline characters if possible
        //    Using \R matches various newline types (LF, CRLF, CR)
        $lines = preg_split('/\R/u', $code);

        // Handle potential empty string at the end if $code ends with a newline
        if (end($lines) === '' && str_ends_with($code, "\n")) {
            // We'll add the trailing newline back later if needed, remove empty element for now
            array_pop($lines);
            $hasTrailingNewline = true;
        } elseif (end($lines) === '' && str_ends_with($code, "\r\n")) {
            array_pop($lines);
            $hasTrailingNewline = true;
        } else {
            $hasTrailingNewline = false;
        }

        if (empty($lines)) {
            return $code; // Nothing to process or only newlines
        }

        // 2. Find the minimum indentation prefix string
        $minIndentPrefix = null;

        foreach ($lines as $line) {
            // Only consider lines that contain non-whitespace characters
            if (trim($line) !== '') {
                // Match the leading whitespace (spaces or tabs)
                if (preg_match('/^(\s*)/u', $line, $matches)) {
                    $currentPrefix = $matches[1];

                    // If this is the first non-empty line or its indent is shorter
                    if ($minIndentPrefix === null || strlen($currentPrefix) < strlen($minIndentPrefix)) {
                        $minIndentPrefix = $currentPrefix;
                    }
                } else {
                    // Line has content but no leading whitespace
                    $minIndentPrefix = '';
                    break; // Cannot dedent further than 0
                }
            }
        }

        // 3. If no common indentation found or it's empty, return original (or slightly cleaned)
        if ($minIndentPrefix === null || $minIndentPrefix === '') {
            // Re-add trailing newline if it was present
            return $code;
            // Alternative: return implode("\n", $lines) . ($hasTrailingNewline ? "\n" : '');
        }

        // 4. Remove the minimum indentation prefix from each line
        $dedentedLines = [];
        $prefixLength = strlen($minIndentPrefix);

        foreach ($lines as $line) {
            // Check if the line starts with the exact prefix we found
            if (str_starts_with($line, $minIndentPrefix)) {
                // Remove the prefix
                $dedentedLines[] = substr($line, $prefixLength);
            } else {
                // Keep the line as is (might be a blank line or one with less indentation)
                // This part handles lines that were purely whitespace and shorter
                // than the minIndentPrefix - they might become empty or shorter.
                $dedentedLines[] = ltrim($line, $minIndentPrefix); // Safely trim prefix chars if present
                // Or simply: $dedentedLines[] = $line; if you want to be stricter
            }
        }

        // 5. Join the lines back together
        $result = implode("\n", $dedentedLines);

        // 6. Re-add trailing newline if it was originally present
        if ($hasTrailingNewline) {
            $result .= "\n";
        }

        return $result;
    }
}
