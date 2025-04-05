<?php

declare(strict_types=1);

namespace Croft\Feature\Resource;

/**
 * Parser for URI templates
 *
 * Handles converting URI templates with {param} syntax to regex patterns
 * and extracting parameters from URIs.
 */
class UriTemplateParser
{
    /**
     * Convert a URI template to a regex pattern for matching
     *
     * @param  string  $template  The URI template with {param} placeholders
     * @return string The regex pattern with named capture groups
     */
    public function templateToPattern(string $template): string
    {
        // Escape the template for regex, but not the curly braces we want to transform
        $escaped = str_replace(['/', '.'], ['\/', '\.'], $template);

        // Convert {param} syntax to regex named capture groups (?<param>[^/]+)
        // This will capture everything between / characters for each parameter
        return preg_replace('/\{([^}]+)\}/', '(?<$1>[^\/]+)', $escaped);
    }

    /**
     * Check if a URI matches a template and extract parameters
     *
     * @param  string  $uri  The URI to match
     * @param  string  $template  The template pattern with {param} placeholders
     * @return array|null Extracted parameters or null if no match
     */
    public function match(string $uri, string $template): ?array
    {
        $pattern = $this->templateToPattern($template);
        $matches = [];

        // Match URI against the generated pattern
        if (preg_match('/^'.$pattern.'$/', $uri, $matches)) {
            // Filter out numeric keys to keep only named captures
            $params = array_filter($matches, function ($key) {
                return ! is_numeric($key);
            }, ARRAY_FILTER_USE_KEY);

            return $params;
        }

        return null;
    }
}
