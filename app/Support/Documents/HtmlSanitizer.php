<?php

namespace App\Support\Documents;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class HtmlSanitizer
{
    private const ALLOWED = [
        'div' => ['style', 'class'],
        'p' => ['style', 'class'],
        'br' => [],
        'span' => ['style'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'sup' => [], 'sub' => [],
        'h1' => ['style'], 'h2' => ['style'], 'h3' => ['style'],
        'ul' => ['style'], 'ol' => ['style', 'start'], 'li' => ['style'],
        'table' => ['style', 'class'],
        'thead' => ['style'], 'tbody' => ['style'], 'tfoot' => ['style'],
        'tr' => ['style'],
        'td' => ['style', 'colspan', 'rowspan', 'align', 'width'],
        'th' => ['style', 'colspan', 'rowspan', 'align', 'width'],
        'img' => ['src', 'alt', 'style', 'width', 'height'],
        'hr' => ['style'],
    ];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'head'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        self::dropForbidden($document);
        self::scrub($body);

        $clean = '';

        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private static function dropForbidden(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $query = implode(' | ', array_map(fn (string $tag): string => '//'.$tag, self::DROP_WITH_CONTENT));

        /** @var iterable<DOMNode> $nodes */
        $nodes = $xpath->query($query) ?: [];

        foreach (iterator_to_array($nodes) as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    private static function scrub(DOMElement $element): void
    {
        foreach (iterator_to_array($element->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            self::scrub($child);

            $tag = strtolower($child->nodeName);

            if (! array_key_exists($tag, self::ALLOWED)) {
                self::unwrap($child);

                continue;
            }

            self::stripAttributes($child, self::ALLOWED[$tag]);
        }
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            $parent->insertBefore($child, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private static function stripAttributes(DOMElement $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'style') {
                $style = self::style($attribute->value);

                $style === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $style);
            }

            if ($name === 'src' && ! str_starts_with($attribute->value, 'data:image/')) {
                $element->parentNode?->removeChild($element);

                return;
            }
        }
    }

    private static function style(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);

            if ($declaration === '' || ! str_contains($declaration, ':')) {
                continue;
            }

            $lower = strtolower($declaration);

            if (str_contains($lower, 'url(') || str_contains($lower, 'expression(') || str_contains($lower, 'javascript:')) {
                continue;
            }

            $kept[] = $declaration;
        }

        return implode('; ', $kept);
    }
}
