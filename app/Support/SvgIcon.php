<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use enshrined\svgSanitize\Sanitizer;

/**
 * An imported SVG made safe to serve and able to scale: scripts, event
 * handlers and remote references are removed, and the drawing gets a viewBox
 * instead of a fixed size, so CSS can show it at the size of the text.
 */
final readonly class SvgIcon
{
    private function __construct(
        public string $svg,
        // A single colour (or none): it can follow the colour of the text.
        public bool $monochrome,
    ) {}

    /**
     * The cleaned icon, or null when the content is not an SVG drawing.
     */
    public static function clean(string $content): ?self
    {
        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(true);

        $sanitized = $sanitizer->sanitize($content);

        if (! is_string($sanitized) || $sanitized === '') {
            return null;
        }

        $document = new DOMDocument;

        if (! @$document->loadXML($sanitized, LIBXML_NONET)) {
            return null;
        }

        $root = $document->documentElement;

        if ($root === null || $root->localName !== 'svg') {
            return null;
        }

        self::makeScalable($root);

        $svg = $document->saveXML($root);

        return $svg === false ? null : new self($svg, self::isMonochrome($document));
    }

    private static function makeScalable(DOMElement $root): void
    {
        if (! $root->hasAttribute('viewBox')) {
            $width = self::length($root->getAttribute('width'));
            $height = self::length($root->getAttribute('height'));

            if ($width === null || $height === null) {
                return;
            }

            $root->setAttribute('viewBox', "0 0 {$width} {$height}");
        }

        $root->removeAttribute('width');
        $root->removeAttribute('height');
    }

    /** A length in user units ("24", "24px"); null for "100%", "2em"… */
    private static function length(string $value): ?string
    {
        return preg_match('/^\s*(\d+(?:\.\d+)?)\s*(?:px)?\s*$/', $value, $match) === 1 && (float) $match[1] > 0
            ? $match[1]
            : null;
    }

    private static function isMonochrome(DOMDocument $document): bool
    {
        $colours = [];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (in_array($element->localName, ['linearGradient', 'radialGradient', 'pattern', 'image'], true)) {
                return false;
            }

            foreach (['fill', 'stroke', 'stop-color', 'color'] as $attribute) {
                $colours[] = $element->getAttribute($attribute);
            }

            $css = $element->getAttribute('style').($element->localName === 'style' ? $element->textContent : '');
            preg_match_all('/(?:fill|stroke|stop-color|color)\s*:\s*([^;}!]+)/i', $css, $matches);
            array_push($colours, ...$matches[1]);
        }

        $distinct = array_unique(array_filter(array_map(self::normalise(...), $colours)));

        return count($distinct) <= 1;
    }

    /** The colour as a comparable string; null when it is no colour of its own. */
    private static function normalise(string $colour): ?string
    {
        $colour = strtolower(trim($colour));

        if (in_array($colour, ['', 'none', 'transparent', 'currentcolor', 'inherit'], true)) {
            return null;
        }

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $colour, $short) === 1) {
            return "#{$short[1]}{$short[1]}{$short[2]}{$short[2]}{$short[3]}{$short[3]}";
        }

        return $colour;
    }
}
