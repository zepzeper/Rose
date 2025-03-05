<?php

namespace Rose\View\Htmx;

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
     * Get the HX-Prompt response if available
     *
     * @return string|null
     */
    public static function getPromptResponse(): ?string
    {
        return $_SERVER['HTTP_HX_PROMPT'] ?? null;
    }

    /**
     * Get the parent ID if the request is from an hx-push-url
     *
     * @return string|null
     */
    public static function getPushUrl(): ?string
    {
        return $_SERVER['HTTP_HX_PUSH_URL'] ?? null;
    }

    /**
     * Get the history restoration info
     *
     * @return string|null
     */
    public static function getHistoryRestoreRequest(): ?string
    {
        return $_SERVER['HTTP_HX_HISTORY_RESTORE_REQUEST'] ?? null;
    }

    /**
     * Get the target element history
     *
     * @return array|null
     */
    public static function getTriggerExt(): ?array
    {
        $triggerExt = $_SERVER['HTTP_HX_TRIGGER_EXT'] ?? null;
        
        if ($triggerExt) {
            return json_decode($triggerExt, true);
        }
        
        return null;
    }

    /**
     * Check if the request is a history restoration request
     *
     * @return bool
     */
    public static function isHistoryRestoreRequest(): bool
    {
        return isset($_SERVER['HTTP_HX_HISTORY_RESTORE_REQUEST']) && $_SERVER['HTTP_HX_HISTORY_RESTORE_REQUEST'] === 'true';
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

    /**
     * Apply headers to a PSR-7 response
     *
     * @param ResponseInterface $response PSR-7 response object
     * @return ResponseInterface
     */
    public static function applyHeaders($response)
    {
        foreach (self::$headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }

    /**
     * Apply headers directly using the PHP header function
     *
     * @return void
     */
    public static function sendHeaders(): void
    {
        foreach (self::$headers as $name => $value) {
            header("$name: $value");
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
     * Trigger client-side event after the settlement step
     *
     * @param string|array $events Event name or associative array of events
     * @param mixed $detail Optional event details for single event
     * @return void
     */
    public static function triggerAfterSettle($events, $detail = null): void
    {
        if (is_string($events) && $detail !== null) {
            $events = [$events => $detail];
        }

        self::header('Trigger-After-Settle', $events);
    }

    /**
     * Trigger client-side event after the swap step
     *
     * @param string|array $events Event name or associative array of events
     * @param mixed $detail Optional event details for single event
     * @return void
     */
    public static function triggerAfterSwap($events, $detail = null): void
    {
        if (is_string($events) && $detail !== null) {
            $events = [$events => $detail];
        }

        self::header('Trigger-After-Swap', $events);
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
     * Pushes a new URL into the history stack
     *
     * @param string $url The URL to push into history
     * @return void
     */
    public static function pushUrl(string $url): void
    {
        self::header('Push-Url', $url);
    }

    /**
     * Replaces the current URL in the location bar
     *
     * @param string $url The URL to replace in history
     * @return void
     */
    public static function replaceUrl(string $url): void
    {
        self::header('Replace-Url', $url);
    }

    /**
     * Sets the location property of window.location
     *
     * @param string $url The URL to set
     * @return void
     */
    public static function location(string $url): void
    {
        self::header('Location', $url);
    }

    /**
     * Shows an alert to the user with the specified message
     *
     * @param string $message The message to show
     * @return void
     */
    public static function alert(string $message): void
    {
        self::header('Alert', $message);
    }

    /**
     * Sets the title of the page
     *
     * @param string $title The title to set
     * @return void
     */
    public static function title(string $title): void
    {
        self::header('Title', $title);
    }

    /**
     * Add a CSS class to the target element
     *
     * @param string $classes Space-separated list of classes
     * @return void
     */
    public static function addClass(string $classes): void
    {
        self::header('Add-Class', $classes);
    }

    /**
     * Remove a CSS class from the target element
     *
     * @param string $classes Space-separated list of classes
     * @return void
     */
    public static function removeClass(string $classes): void
    {
        self::header('Remove-Class', $classes);
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

    /**
     * Generates a button with HTMX attributes
     *
     * @param string $content Button text/content
     * @param array $attributes Button attributes
     * @return string Button HTML
     */
    public static function button(string $content, array $attributes = []): string
    {
        // Ensure we have a button type if not specified
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'button';
        }
        
        return self::element('button', $attributes, $content);
    }

    /**
     * Generates a link with HTMX attributes
     *
     * @param string $content Link text/content
     * @param string $href Link URL
     * @param array $attributes Link attributes
     * @return string Link HTML
     */
    public static function link(string $content, string $href, array $attributes = []): string
    {
        $attributes['href'] = $href;
        return self::element('a', $attributes, $content);
    }

    /**
     * Generates a form with HTMX attributes
     *
     * @param string $content Form content
     * @param string $action Form action URL
     * @param string $method Form method
     * @param array $attributes Form attributes
     * @return string Form HTML
     */
    public static function form(string $content, string $action, string $method = 'post', array $attributes = []): string
    {
        $attributes['action'] = $action;
        $attributes['method'] = $method;
        
        return self::element('form', $attributes, $content);
    }

    /**
     * Generates input with HTMX attributes
     *
     * @param string $type Input type
     * @param string $name Input name
     * @param string|null $value Input value
     * @param array $attributes Input attributes
     * @return string Input HTML
     */
    public static function input(string $type, string $name, $value = null, array $attributes = []): string
    {
        $attributes['type'] = $type;
        $attributes['name'] = $name;
        
        if ($value !== null) {
            $attributes['value'] = $value;
        }
        
        return self::element('input', $attributes);
    }

    /**
     * Create a div that will be loaded dynamically (lazy loading)
     *
     * @param string $url URL to load content from
     * @param string|null $content Initial content (spinner, etc)
     * @param array $attributes Additional attributes
     * @return string HTML div with hx-get attribute
     */
    public static function lazyLoad(string $url, $content = null, array $attributes = []): string
    {
        $attributes['hx-get'] = $url;
        
        if (!isset($attributes['hx-trigger'])) {
            $attributes['hx-trigger'] = 'revealed';
        }
        
        return self::element('div', $attributes, $content ?? '<div class="htmx-indicator">Loading...</div>');
    }

    /**
     * Create an infinite scroll element
     *
     * @param string $url URL to load more content from
     * @param string|null $content Initial content
     * @param array $attributes Additional attributes
     * @return string HTML div with infinite scroll HTMX attributes
     */
    public static function infiniteScroll(string $url, $content = null, array $attributes = []): string
    {
        $attributes['hx-get'] = $url;
        $attributes['hx-trigger'] = 'revealed';
        
        if (!isset($attributes['hx-swap'])) {
            $attributes['hx-swap'] = 'beforeend';
        }
        
        return self::element('div', $attributes, $content);
    }

    /**
     * Create a polling element that refreshes content at intervals
     *
     * @param string $url URL to poll for content
     * @param int $interval Interval in milliseconds
     * @param string|null $content Initial content
     * @param array $attributes Additional attributes
     * @return string HTML element with polling HTMX attributes
     */
    public static function poll(string $url, int $interval = 2000, $content = null, array $attributes = []): string
    {
        $attributes['hx-get'] = $url;
        $attributes['hx-trigger'] = "every {$interval}ms";
        
        return self::element('div', $attributes, $content);
    }

    /**
     * Create a clickToEdit pattern element
     *
     * @param string $viewUrl URL to load view mode content
     * @param string $editUrl URL to load edit mode content
     * @param string|null $content Initial content
     * @param array $attributes Additional attributes
     * @return string HTML for the click-to-edit pattern
     */
    public static function clickToEdit(string $viewUrl, string $editUrl, $content = null, array $attributes = []): string
    {
        // Outer container
        $editAttributes = [
            'hx-get' => $editUrl,
            'hx-trigger' => 'click',
            'class' => 'htmx-click-to-edit ' . ($attributes['class'] ?? '')
        ];
        
        // Merge in any other attributes
        foreach ($attributes as $key => $value) {
            if ($key !== 'class') {
                $editAttributes[$key] = $value;
            }
        }
        
        return self::element('div', $editAttributes, $content);
    }

    /**
     * Create a progress bar that will be updated by HTMX
     *
     * @param string $url URL to poll for progress updates
     * @param int $interval Polling interval in milliseconds
     * @param int $initialValue Initial progress value (0-100)
     * @param array $attributes Additional attributes
     * @return string HTML progress element
     */
    public static function progressBar(string $url, int $interval = 500, int $initialValue = 0, array $attributes = []): string
    {
        // Progress attributes
        $progressAttributes = [
            'hx-get' => $url,
            'hx-trigger' => "every {$interval}ms",
            'hx-target' => 'this',
            'hx-swap' => 'outerHTML',
            'value' => $initialValue,
            'max' => 100
        ];
        
        // Merge in any other attributes
        foreach ($attributes as $key => $value) {
            $progressAttributes[$key] = $value;
        }
        
        return self::element('progress', $progressAttributes);
    }

    /**
     * Create a confirmation dialog using hx-confirm
     *
     * @param string $content Element content
     * @param string $confirmMessage Confirmation message
     * @param array $attributes Additional attributes
     * @param string $tag HTML tag to use
     * @return string HTML element with confirmation
     */
    public static function confirm(string $content, string $confirmMessage, array $attributes = [], string $tag = 'button'): string
    {
        $attributes['hx-confirm'] = $confirmMessage;
        return self::element($tag, $attributes, $content);
    }

    /**
     * Create a debounced input
     * 
     * @param string $url URL to send input value to
     * @param string $name Input name
     * @param string|null $value Initial value
     * @param int $delay Debounce delay in milliseconds
     * @param array $attributes Additional attributes
     * @return string HTML input with debounce
     */
    public static function debouncedInput(string $url, string $name, $value = null, int $delay = 500, array $attributes = []): string
    {
        $attributes['hx-post'] = $url;
        $attributes['hx-trigger'] = "keyup changed delay:{$delay}ms";
        
        return self::input('text', $name, $value, $attributes);
    }

    /**
     * Create an element that confirms its action
     *
     * @param string $content Element content
     * @param string $url URL to send the request to
     * @param string $confirmMessage Confirmation message
     * @param string $method HTTP method (get, post, etc)
     * @param array $attributes Additional attributes
     * @return string HTML element with confirmation
     */
    public static function confirmAction(string $content, string $url, string $confirmMessage, string $method = 'get', array $attributes = []): string
    {
        $attributes["hx-{$method}"] = $url;
        $attributes['hx-confirm'] = $confirmMessage;
        
        return self::element('button', $attributes, $content);
    }

    /**
     * Create a tabs interface
     *
     * @param array $tabs Array of tab data, each with 'id', 'label', and 'url' keys
     * @param string|null $activeTabId ID of initially active tab
     * @param array $attributes Additional attributes for container
     * @return string HTML for tabbed interface
     */
    public static function tabs(array $tabs, ?string $activeTabId = null, array $attributes = []): string
    {
        // Set default active tab if not specified
        if ($activeTabId === null && !empty($tabs)) {
            $activeTabId = $tabs[0]['id'];
        }
        
        // Create tab navigation
        $tabNav = '';
        foreach ($tabs as $tab) {
            $isActive = $tab['id'] === $activeTabId;
            
            $tabAttrs = [
                'hx-get' => $tab['url'],
                'hx-target' => '#tab-content',
                'class' => 'tab' . ($isActive ? ' active' : '')
            ];
            
            $tabNav .= self::element('button', $tabAttrs, $tab['label']);
        }
        
        // Find the active tab's URL
        $activeTabUrl = '';
        foreach ($tabs as $tab) {
            if ($tab['id'] === $activeTabId) {
                $activeTabUrl = $tab['url'];
                break;
            }
        }
        
        // Create the tab content area, initially loaded with active tab
        $tabContent = self::element('div', [
            'id' => 'tab-content',
            'hx-get' => $activeTabUrl,
            'hx-trigger' => 'load',
        ]);
        
        // Build the complete tabbed interface
        $tabsNav = self::element('div', ['class' => 'tabs-nav'], $tabNav);
        $tabsContainer = $tabsNav . $tabContent;
        
        return self::element('div', array_merge(['class' => 'tabs-container'], $attributes), $tabsContainer);
    }
}
