<?php

declare(strict_types=1);

namespace Croft\Tools\Flux;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Croft\Http\HttpClient;
use DOMDocument;
use DOMXPath;

class GetComponentDetails extends AbstractTool
{
    private const FLUX_DOCS_URL = 'https://fluxui.dev';

    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Get Flux Component Details')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(true);     // Accessing external URLs
    }

    public function getName(): string
    {
        return 'get_flux_component_details';
    }

    public function getDescription(): string
    {
        return 'Get detailed information about a specific Flux UI component';
    }

    /**
     * What params does the MCP client need to provide to use this tool?
     **/
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'componentName' => [
                    'type' => 'string',
                    'description' => 'Name of the Flux UI component (e.g., "accordion", "button", "date-picker")',
                ],
            ],
            'required' => ['componentName'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        try {
            $componentName = $arguments['componentName'];

            // Check cache first
            if ($this->cache->has('flux_component_details_'.$componentName)) {
                return ToolResponse::array($this->cache->get('flux_component_details_'.$componentName));
            }

            // Fetch component details
            $response = HttpClient::getInstance()->get(self::FLUX_DOCS_URL.'/components/'.$componentName);

            // TODO: Validate it's a valid component name

            if (! $response->successful()) {
                return ToolResponse::error('Failed to fetch component details: HTTP '.$response->status());
            }

            $html = $response->body();
            $dom = new DOMDocument;
            @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            dump($this->extractParams($dom));

            // Parse component details
            $details = [
                'name' => $componentName,
                'url' => self::FLUX_DOCS_URL.'/components/'.$componentName,
                'description' => $this->extractDescription($dom),
                'props' => $this->extractParams($dom),
                'examples' => $this->extractExamples($dom),
            ];

            // Cache the results
            $this->cache->set('flux_component_details_'.$componentName, $details);

            return ToolResponse::array($details);
        } catch (\Exception $e) {
            return ToolResponse::error('Failed to fetch component details: '.$e->getMessage());
        }
    }

    private function extractDescription(DOMDocument $dom): string
    {
        $xpath = new DOMXPath($dom);
        $paragraphs = $xpath->query('//p[1]');
        if ($paragraphs->length > 0) {
            return trim($paragraphs->item(0)->textContent);
        }

        return '';
    }

    private function extractParams(DOMDocument $dom): array
    {
        $data = [];
        $xpath = new DOMXPath($dom);

        // Find all h3 elements that contain component tags
        $h3Elements = $xpath->query('//h3[contains(@class, "font-semibold")]');

        foreach ($h3Elements as $h3) {
            // Extract the tag name from the h3 element
            $tagLink = $xpath->query('.//a', $h3)->item(0);
            if (! $tagLink) {
                continue;
            }

            $tag = trim($tagLink->textContent);
            $data[$tag] = [
                'attributes' => [],
                'props' => [],
                'slots' => [],
            ];

            // Find all tables that follow this h3 until the next h3
            $currentNode = $h3;
            while ($currentNode = $currentNode->nextSibling) {
                if ($currentNode->nodeName === 'h3') {
                    break;
                }

                // Find tables within this section
                $tables = $xpath->query('.//table', $currentNode);
                foreach ($tables as $table) {
                    // Determine table type (props, attributes, or slots) from the header
                    $header = $xpath->query('.//th[1]//div', $table)->item(0);
                    if (! $header) {
                        continue;
                    }

                    $type = strtolower(trim($header->textContent));
                    $rows = $xpath->query('.//tbody/tr', $table);

                    foreach ($rows as $row) {
                        $cells = $xpath->query('.//td', $row);
                        if ($cells->length < 2) {
                            continue;
                        }

                        $name = trim($cells->item(0)->textContent);
                        $description = trim($cells->item(1)->textContent);

                        switch ($type) {
                            case 'prop':
                                $data[$tag]['props'][$name] = $description;
                                break;
                            case 'attribute':
                                $data[$tag]['attributes'][$name] = $description;
                                break;
                            case 'slot':
                                $data[$tag]['slots'][$name] = $description;
                                break;
                        }
                    }
                }
            }
        }

        return $data;
    }

    private function extractExamples(DOMDocument $dom): array
    {
        $xpath = new DOMXPath($dom);
        $examples = [];

        // Find all pre elements that contain code examples
        $preElements = $xpath->query('//pre');
        foreach ($preElements as $preElement) {
            $code = trim($preElement->textContent);
            if (empty($code)) {
                continue;
            }

            $title = 'Code Example';
            $description = null;

            // Strategy 1: Look for closest heading and its description
            $heading = $xpath->query('preceding::h3[1]', $preElement);
            if ($heading->length > 0) {
                $title = trim($heading->item(0)->textContent);

                // Look for the description div that follows this heading
                $descriptionDiv = $xpath->query('following-sibling::div[contains(@class, "text-zinc-500")]', $heading->item(0));
                if ($descriptionDiv->length > 0) {
                    // Find the paragraph within the description div
                    $descriptionP = $xpath->query('.//p', $descriptionDiv->item(0));
                    if ($descriptionP->length > 0) {
                        $description = trim($descriptionP->item(0)->textContent);
                    }
                }
            }

            // Strategy 2: If no h3 found, look for h2
            if ($title === 'Code Example') {
                $heading = $xpath->query('preceding::h2[1]', $preElement);
                if ($heading->length > 0) {
                    $title = trim($heading->item(0)->textContent);

                    // Look for the description div that follows this heading
                    $descriptionDiv = $xpath->query('following-sibling::div[contains(@class, "text-zinc-500")]', $heading->item(0));
                    if ($descriptionDiv->length > 0) {
                        // Find the paragraph within the description div
                        $descriptionP = $xpath->query('.//p', $descriptionDiv->item(0));
                        if ($descriptionP->length > 0) {
                            $description = trim($descriptionP->item(0)->textContent);
                        }
                    }
                }
            }

            // Strategy 3: Check for tab panel structure (keeping this as fallback)
            if ($title === 'Code Example') {
                $tabPanel = $xpath->query('ancestor::*[@role="tabpanel"]', $preElement);
                if ($tabPanel->length > 0) {
                    $labelledBy = $tabPanel->item(0)->attributes->getNamedItem('aria-labelledby');
                    if ($labelledBy) {
                        $tabButton = $xpath->query("//*[@id='".$labelledBy->value."']");
                        if ($tabButton->length > 0 && strtolower(trim($tabButton->item(0)->textContent)) === 'code') {
                            $heading = $xpath->query('ancestor::div/preceding-sibling::h2|ancestor::div/preceding-sibling::h3', $tabButton->item(0));
                            if ($heading->length > 0) {
                                $title = trim($heading->item(0)->textContent);
                            }
                        }
                    }
                }
            }

            $examples[] = [
                'title' => $title,
                'code' => $code,
                'description' => $description,
            ];
        }

        // Deduplicate examples based on code content
        $uniqueExamples = [];
        $seenCodes = [];
        foreach ($examples as $example) {
            if (! isset($seenCodes[$example['code']])) {
                $uniqueExamples[] = $example;
                $seenCodes[$example['code']] = true;
            }
        }

        return $uniqueExamples;
    }

    /**
     * Finds the import statement within the extracted examples
     */
    private function findImportStatement(array $examples): ?string
    {
        foreach ($examples as $example) {
            // Try first pattern
            if (preg_match('/import\s+{.*}\s+from\s+[\'"]@fluxui\/core[\'"]\;?/', $example['code'], $matches)) {
                return $matches[0];
            }
        }

        // Fallback pattern
        foreach ($examples as $example) {
            if (preg_match('/import\s+.*\s+from\s+[\'"]@fluxui\/.*[\'"]\;?/', $example['code'], $matches)) {
                return $matches[0];
            }
        }

        return null;
    }
}
