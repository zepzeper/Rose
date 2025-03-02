<?php

namespace Rose\View;

class Vite
{
    protected static $devServerUrl = 'http://localhost:5173';

    protected static $manifestPath = "public/dist/manifest.json";

    /**
     * @var array|null $manifest Cache for the manifest
     */
    protected static $manifest = null;

    /**
     * @var string|bool $serverRunning Cache for dev server running
     */
    protected static $devServerRunning = null;

    /**
     * Generate appropiate tags for the given entry point.
     *
     * @param string $entry The entry point path relative to the root of the project
     * @return string HTML tags for the CSS and JS.
     */
    public static function tags(string $entry = "resources/js/main.js") 
    {
        if (self::isDevServerRunning())
        {
            return self::devServerTags($entry);
        }

        return self::productionTags($entry);
    }

    /**
     * Check if the Vite dev server is running
     *
     * @return bool
     */
    protected static function isDevServerRunning() 
    {
        if (self::$devServerRunning)
        {
            return self::$devServerRunning;
        }

        // Check if the Vite dev server is responding
        $handle = @fsockopen(parse_url(self::$devServerUrl, PHP_URL_HOST), 
            parse_url(self::$devServerUrl, PHP_URL_PORT) ?: 80, 
            $errno, $errstr, 1);

        $isRunning = false; 

        if ($handle)
        {
            $isRunning = true;
            fclose($handle);
        }

        return self::$devServerRunning = $isRunning;
    }

    /**
     * Generate tags for development mode using Vite dev server
     *
     * @param string $entry The entry point
     * @return string HTML tags
     */
    protected static function devServerTags(string $entry): string
    {
        $devServerUrl = rtrim(self::$devServerUrl, '/');
        $entryUrl = $devServerUrl . '/' . ltrim($entry, '/');

        return <<<HTML
            <script type="module" src="{$devServerUrl}/@vite/client"></script>
            <script type="module" src="{$entryUrl}"></script>
            HTML;
    }    

    /**
     * Generate tags for production mode using built assets
     *
     * @param string $entry The entry point
     * @return string HTML tags
     */
    protected static function productionTags(string $entry): string
    {
        $manifest = self::getManifest();

        if (!isset($manifest[$entry])) {
            return "<!-- Vite: Entry point '{$entry}' not found in manifest -->";
        }

        $tags = [];
        $entryData = $manifest[$entry];

        // Add CSS files if available
        if (isset($entryData['css']) && is_array($entryData['css'])) {
            foreach ($entryData['css'] as $cssFile) {
                $tags[] = '<link rel="stylesheet" href="/dist/' . $cssFile . '">';
            }
        }

        // Add the main JS file
        $tags[] = '<script type="module" src="/dist/' . $entryData['file'] . '"></script>';

        // Add imported JS files if available
        if (isset($entryData['imports']) && is_array($entryData['imports'])) {
            foreach ($entryData['imports'] as $import) {
                $tags[] = '<link rel="modulepreload" href="/dist/' . $manifest[$import]['file'] . '">';
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Get the manifest data
     *
     * @return array
     */
    protected static function getManifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        if (!file_exists(self::$manifestPath)) {
            return self::$manifest = [];
        }

        return self::$manifest = json_decode(file_get_contents(self::$manifestPath), true) ?: [];
    }

    /**
     * Get the URL for an asset from the manifest
     *
     * @param string $path The asset path
     * @return string The asset URL
     */
    public static function asset(string $path): string
    {
        if (self::isDevServerRunning()) {
            return rtrim(self::$devServerUrl, '/') . '/' . ltrim($path, '/');
        }

        $manifest = self::getManifest();

        if (!isset($manifest[$path])) {
            return '/dist/' . $path; // Fallback to direct path
        }

        return '/dist/' . $manifest[$path]['file'];
    }

    /**
     * Configure the Vite helper
     *
     * @param array $config Configuration options
     * @return void
     */
    public static function config(array $config): void
    {
        if (isset($config['devServerUrl'])) {
            self::$devServerUrl = $config['devServerUrl'];
        }

        if (isset($config['manifestPath'])) {
            self::$manifestPath = $config['manifestPath'];
        }
    }


}
