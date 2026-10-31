<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for CMS content (pages & journal posts).
 *
 * The admin editor produces HTML in the browser, but the browser is never
 * trusted: everything is re-parsed and rebuilt here on save. Only a small set
 * of formatting tags and attributes survives; scripts, styles, event handlers,
 * iframes, forms, inline styles/classes and `javascript:` URLs are removed.
 * Unknown-but-harmless wrappers (span, div, font…) are unwrapped so pasted
 * Word/Google-Docs content keeps its text.
 */
class HtmlSanitizer
{
    /** tag => allowed attributes */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'em' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];

    /** Presentational tags rewritten to their allowed semantic equivalent. */
    private const RENAME = [
        'b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's',
        'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4',
    ];

    /** Removed together with everything inside them. */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select',
        'textarea', 'link', 'meta', 'noscript', 'svg', 'math', 'template', 'head', 'title', 'base',
    ];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        // The meta tag forces UTF-8; LIBXML_NONET blocks any external fetch.
        $doc->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="__root">' . $html . '</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return '';
        }
        $this->cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $this->tidy($out);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        // Snapshot: we mutate the list while walking it.
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_COMMENT_NODE || $node->nodeType === XML_PI_NODE || $node->nodeType === XML_CDATA_SECTION_NODE) {
                $parent->removeChild($node);
                continue;
            }
            if (! $node instanceof DOMElement) {
                continue; // text nodes are safe (re-escaped on output)
            }

            $tag = strtolower($node->tagName);
            if (isset(self::RENAME[$tag])) {
                $node = $this->rename($node, self::RENAME[$tag]);
                $tag  = strtolower($node->tagName);
            }

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);
                continue;
            }

            if (! isset(self::ALLOWED[$tag])) {
                // Unwrap: keep the children, drop the element itself.
                $this->cleanChildren($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $this->cleanAttributes($node, $tag);
            if ($tag === 'img' && ! $node->getAttribute('src')) {
                $parent->removeChild($node);
                continue;
            }
            if ($tag === 'a' && ! $node->getAttribute('href')) {
                // A link without a safe target is just text.
                $this->cleanChildren($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $this->cleanChildren($node);
        }
    }

    private function rename(DOMElement $el, string $newTag): DOMElement
    {
        $new = $el->ownerDocument->createElement($newTag);
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    private function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a') {
            $href = $this->safeUrl($el->getAttribute('href'), ['http', 'https', 'mailto', 'tel']);
            if ($href === null) {
                $el->removeAttribute('href');
            } else {
                $el->setAttribute('href', $href);
                // Only "open in new tab" survives, and it always gets noopener.
                if ($el->getAttribute('target') === '_blank') {
                    $el->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $el->removeAttribute('target');
                    $el->removeAttribute('rel');
                }
            }
        }

        if ($tag === 'img') {
            $src = $this->safeUrl($el->getAttribute('src'), ['http', 'https']);
            if ($src === null) {
                $el->removeAttribute('src');
            } else {
                $el->setAttribute('src', $src);
            }
            $el->setAttribute('loading', 'lazy');
            foreach (['width', 'height'] as $dim) {
                if ($el->hasAttribute($dim) && ! ctype_digit($el->getAttribute($dim))) {
                    $el->removeAttribute($dim);
                }
            }
        }

        foreach (['colspan', 'rowspan'] as $span) {
            if ($el->hasAttribute($span) && ! ctype_digit($el->getAttribute($span))) {
                $el->removeAttribute($span);
            }
        }
    }

    /**
     * Returns a safe URL, or null. Relative URLs, anchors and the allowed
     * schemes pass; everything else (javascript:, data:, vbscript:…) fails.
     */
    private function safeUrl(string $url, array $schemes): ?string
    {
        // Browsers ignore whitespace/control chars inside the scheme ("java\tscript:").
        $url = trim(preg_replace('/[\x00-\x20\x7f]+/u', ' ', $url) ?? '');
        if ($url === '') {
            return null;
        }
        $compact = preg_replace('/\s+/', '', $url);
        if (preg_match('#^([a-z][a-z0-9+.\-]*):#i', $compact, $m)) {
            return in_array(strtolower($m[1]), $schemes, true) ? $url : null;
        }
        // No scheme: reject protocol-relative-with-backslash tricks, allow the rest.
        if (str_starts_with($compact, '//') || str_contains($compact, '\\')) {
            return null;
        }

        return $url;
    }

    /** Drop empty paragraphs and trim stray whitespace left by the editor. */
    private function tidy(string $html): string
    {
        $html = preg_replace('#<p>(\s|&nbsp;|\x{00A0}|<br\s*/?>)*</p>#iu', '', $html) ?? $html;

        return trim($html);
    }
}
