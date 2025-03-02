<?php

namespace Rose\View\Htmx;

use Psr\Http\Message\ServerRequestInterface;

/**
 * HTMX Helper for PHP framework
 */
class HtmxHelper
{

    protected static $headers = [];

    /**
     * Check if the current request is an HTMX request
     *
     * @return bool
     */
    public static function isHtmxRequest(): bool
    {
        return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
    }

    /**
     * Check if the request was boosted by hx-boost
     *
     * @return bool
     */
    public static function isBoosted(): bool
    {
        return isset($_SERVER['HTTP_HX_BOOSTED']) && $_SERVER['HTTP_HX_BOOSTED'] === 'true';
    }

    /**
     * Get the HTMX target element
     *
     * @return string|null
     */
    public static function getTarget(): ?string
    {
        return $_SERVER['HTTP_HX_TARGET'] ?? null;
    }

    /**
     * Get the HTMX trigger element
     *
     * @return string|null
     */
    public static function getTrigger(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER'] ?? null;
    }

    /**
     * Get the HTMX trigger name
     *
     * @return string|null
     */
    public static function getTriggerName(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER_NAME'] ?? null;
    }

    /**
     * Get the current URL from the HX-Current-URL header
     *
     * @return string|null
     */
    public static function getCurrentUrl(): ?string
    {
        return $_SERVER['HTTP_HX_CURRENT_URL'] ?? null;
    }

    /**
     * Set HTMX response headers
     *
     * @param string $name Header name without HX- prefix
     * @param mixed $value Header value
     * @return void
     */
    public static function header(string $name, $value): void
    {
        // Add HX- prefix if not already present
        if (strpos($name, 'HX-') !== 0) {
            $name = 'HX-' . $name;
        }

        // Convert arrays/objects to JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        self::$headers[$name] = $value;
    }

    /**
     * Get all HTMX headers that need to be applied
     *
     * @return array
     */
    public static function getHeaders(): array
    {
        return self::$headers;
    }
    
    /**
     * Clear all stored headers
     *
     * @return void
     */
    public static function clearHeaders(): void
    {
        self::$headers = [];
    }

    public static function applyHeaders(ServerRequestInterface $response)
    {
        foreach (self::$headers as $name => $value)
        {
            $response->withHeader($name, $value);
        }
    }

    /**
     * Trigger client-side event
     *
     * @param string|array $events Event name or associative array of events
     * @param mixed $detail Optional event details for single event
     * @return void
     */
    public static function trigger($events, $detail = null): void
    {
        if (is_string($events) && $detail !== null) {
            $events = [$events => $detail];
        }

        self::header('Trigger', $events);
    }

    /**
     * Redirect the browser to a new URL
     *
     * @param string $url URL to redirect to
     * @return void
     */
    public static function redirect(string $url): void
    {
        self::header('Redirect', $url);
    }

    /**
     * Force a full refresh of the page
     *
     * @return void
     */
    public static function refresh(): void
    {
        self::header('Refresh', 'true');
    }

    /**
     * Change the target of the response
     *
     * @param string $selector CSS selector
     * @return void
     */
    public static function retarget(string $selector): void
    {
        self::header('Retarget', $selector);
    }

    /**
     * Change the swap method
     *
     * @param string $swapMethod The swap method
     * @return void
     */
    public static function reswap(string $swapMethod): void
    {
        self::header('Reswap', $swapMethod);
    }

    /**
     * Generate HTMX attributes string
     *
     * @param array $attributes HTMX attributes
     * @return string HTML attribute string
     */
    public static function attributes(array $attributes): string
    {
        $result = [];

        foreach ($attributes as $key => $value) {
            // Add hx- prefix if not present
            if (strpos($key, 'hx-') !== 0) {
                $key = 'hx-' . $key;
            }

            if ($value === true) {
                $result[] = $key;
            } elseif ($value !== false && $value !== null) {
                $result[] = $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return implode(' ', $result);
    }

    /**
     * Create a HTML element with HTMX attributes
     *
     * @param string $tag HTML tag
     * @param array $attributes Element attributes
     * @param string|null $content Element content
     * @return string HTML element
     */
    public static function element(string $tag, array $attributes = [], $content = null): string
    {
        $htmlAttrs = [];
        $htmxAttrs = [];

        // Separate HTMX attributes from regular HTML attributes
        foreach ($attributes as $key => $value) {
            if (strpos($key, 'hx-') === 0) {
                $htmxAttrs[$key] = $value;
            } else {
                $htmlAttrs[$key] = $value;
            }
        }

        // Build HTML attribute string
        $attrStr = '';
        foreach ($htmlAttrs as $key => $value) {
            if ($value === true) {
                $attrStr .= ' ' . $key;
            } elseif ($value !== false && $value !== null) {
                $attrStr .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        // Add HTMX attributes
        if (!empty($htmxAttrs)) {
            $attrStr .= ' ' . self::attributes($htmxAttrs);
        }

        // Check if this is a self-closing tag
        $selfClosing = in_array(strtolower($tag), ['img', 'input', 'br', 'hr', 'meta', 'link']);

        if ($selfClosing) {
            return "<{$tag}{$attrStr} />";
        }

        return "<{$tag}{$attrStr}>" . ($content ?? '') . "</{$tag}>";
    }
}
