<?php

namespace Rose\View\Htmx;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HtmxTwigExtension extends AbstractExtension
{
    /**
     * Return the functions registered as twig functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
						// Twig Code blocks
						new TwigFunction('highlight_php', [HtmxMacroSyntaxHighlighter::class, 'highlight'], ['is_safe' => ['html']]),
            // Attribute helpers
            new TwigFunction('htmx_attrs', [$this, 'renderAttributes'], ['is_safe' => ['html']]),
            
            // Element creation
            new TwigFunction('htmx_element', [$this, 'renderElement'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_button', [$this, 'renderButton'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_link', [$this, 'renderLink'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_form', [$this, 'renderForm'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_input', [$this, 'renderInput'], ['is_safe' => ['html']]),
            
            // UI Patterns
            new TwigFunction('htmx_lazy_load', [$this, 'renderLazyLoad'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_infinite_scroll', [$this, 'renderInfiniteScroll'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_poll', [$this, 'renderPoll'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_click_to_edit', [$this, 'renderClickToEdit'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_progress_bar', [$this, 'renderProgressBar'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_confirm', [$this, 'renderConfirm'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_debounced_input', [$this, 'renderDebouncedInput'], ['is_safe' => ['html']]),
            new TwigFunction('htmx_tabs', [$this, 'renderTabs'], ['is_safe' => ['html']]),
            
            // Request detection helpers
            new TwigFunction('htmx_is_request', [$this, 'isHtmxRequest']),
            new TwigFunction('htmx_is_boosted', [$this, 'isBoostedRequest']),
        ];
    }

    /**
     * Render HTMX attributes
     *
     * @param array $attributes HTMX attributes
     * @return string
     */
    public function renderAttributes(array $attributes): string
    {
        return HtmxHelper::attributes($attributes);
    }
    
    /**
     * Render a HTML element with HTMX attributes
     *
     * @param string $tag HTML tag
     * @param array $attributes Element attributes
     * @param string|null $content Element content
     * @return string
     */
    public function renderElement(string $tag, array $attributes = [], $content = null): string
    {
        return HtmxHelper::element($tag, $attributes, $content);
    }
    
    /**
     * Render a button with HTMX attributes
     *
     * @param string $content Button text/content
     * @param array $attributes Button attributes
     * @return string
     */
    public function renderButton(string $content, array $attributes = []): string
    {
        return HtmxHelper::button($content, $attributes);
    }
    
    /**
     * Render a link with HTMX attributes
     *
     * @param string $content Link text/content
     * @param string $href Link URL
     * @param array $attributes Link attributes
     * @return string
     */
    public function renderLink(string $content, string $href, array $attributes = []): string
    {
        return HtmxHelper::link($content, $href, $attributes);
    }
    
    /**
     * Render a form with HTMX attributes
     *
     * @param string $content Form content
     * @param string $action Form action URL
     * @param string $method Form method
     * @param array $attributes Form attributes
     * @return string
     */
    public function renderForm(string $content, string $action, string $method = 'post', array $attributes = []): string
    {
        return HtmxHelper::form($content, $action, $method, $attributes);
    }
    
    /**
     * Render input with HTMX attributes
     *
     * @param string $type Input type
     * @param string $name Input name
     * @param string|null $value Input value
     * @param array $attributes Input attributes
     * @return string
     */
    public function renderInput(string $type, string $name, $value = null, array $attributes = []): string
    {
        return HtmxHelper::input($type, $name, $value, $attributes);
    }
    
    /**
     * Render a div that will be loaded dynamically (lazy loading)
     *
     * @param string $url URL to load content from
     * @param string|null $content Initial content (spinner, etc)
     * @param array $attributes Additional attributes
     * @return string
     */
    public function renderLazyLoad(string $url, $content = null, array $attributes = []): string
    {
        return HtmxHelper::lazyLoad($url, $content, $attributes);
    }
    
    /**
     * Render an infinite scroll element
     *
     * @param string $url URL to load more content from
     * @param string|null $content Initial content
     * @param array $attributes Additional attributes
     * @return string
     */
    public function renderInfiniteScroll(string $url, $content = null, array $attributes = []): string
    {
        return HtmxHelper::infiniteScroll($url, $content, $attributes);
    }
    
    /**
     * Render a polling element that refreshes content at intervals
     *
     * @param string $url URL to poll for content
     * @param int $interval Interval in milliseconds
     * @param string|null $content Initial content
     * @param array $attributes Additional attributes
     * @return string
     */
    public function renderPoll(string $url, int $interval = 2000, $content = null, array $attributes = []): string
    {
        return HtmxHelper::poll($url, $interval, $content, $attributes);
    }
    
    /**
     * Render a clickToEdit pattern element
     *
     * @param string $viewUrl URL to load view mode content
     * @param string $editUrl URL to load edit mode content
     * @param string|null $content Initial content
     * @param array $attributes Additional attributes
     * @return string
     */
    public function renderClickToEdit(string $viewUrl, string $editUrl, $content = null, array $attributes = []): string
    {
        return HtmxHelper::clickToEdit($viewUrl, $editUrl, $content, $attributes);
    }
    
    /**
     * Render a progress bar that will be updated by HTMX
     *
     * @param string $url URL to poll for progress updates
     * @param int $interval Polling interval in milliseconds
     * @param int $initialValue Initial progress value (0-100)
     * @param array $attributes Additional attributes
     * @return string
     */
    public function renderProgressBar(string $url, int $interval = 500, int $initialValue = 0, array $attributes = []): string
    {
        return HtmxHelper::progressBar($url, $interval, $initialValue, $attributes);
    }
    
    /**
     * Render a confirmation dialog using hx-confirm
     *
     * @param string $content Element content
     * @param string $confirmMessage Confirmation message
     * @param array $attributes Additional attributes
     * @param string $tag HTML tag to use
     * @return string
     */
    public function renderConfirm(string $content, string $confirmMessage, array $attributes = [], string $tag = 'button'): string
    {
        return HtmxHelper::confirm($content, $confirmMessage, $attributes, $tag);
    }
    
    /**
     * Render a debounced input
     * 
     * @param string $url URL to send input value to
     * @param string $name Input name
     * @param string|null $value Initial value
     * @param int $delay Debounce delay in milliseconds
     * @param array $attributes Additional attributes
     * @return string
     */
    public function renderDebouncedInput(string $url, string $name, $value = null, int $delay = 500, array $attributes = []): string
    {
        return HtmxHelper::debouncedInput($url, $name, $value, $delay, $attributes);
    }
    
    /**
     * Render a tabs interface
     *
     * @param array $tabs Array of tab data, each with 'id', 'label', and 'url' keys
     * @param string|null $activeTabId ID of initially active tab
     * @param array $attributes Additional attributes for container
     * @return string
     */
    public function renderTabs(array $tabs, ?string $activeTabId = null, array $attributes = []): string
    {
        return HtmxHelper::tabs($tabs, $activeTabId, $attributes);
    }
    
    /**
     * Check if the current request is an HTMX request
     *
     * @return bool
     */
    public function isHtmxRequest(): bool
    {
        return HtmxHelper::isHtmxRequest();
    }
    
    /**
     * Check if the request was boosted by hx-boost
     *
     * @return bool
     */
    public function isBoostedRequest(): bool
    {
        return HtmxHelper::isBoosted();
    }
}
