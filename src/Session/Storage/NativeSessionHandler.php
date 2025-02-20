<?php

namespace Rose\Session\Storage;

use Rose\Exception\Session\SessionInvalidException;
use Rose\Exceptions\Encryption\EncryptionFailureException;
use Rose\Roots\Application;
use Rose\Encryption\Encryption;
use RuntimeException;

class NativeSessionHandler extends AbstractSessionHandler
{
    /**
     * Flag to track if session has been started
     * @var bool
     */
    private bool $sessionStarted = false;

    /**
     * Tracks if session configuration has been applied
     * @var bool
     */
    private bool $sessionConfigured = false;

    public function __construct(Application $app, Encryption $encryptor, array $options = [])
    {
        parent::__construct($app, $encryptor, $options);

        $this->ensureOutputBuffering();
    }

    /**
    * Ensure output buffering is active to prevent header issues.
    */
    private function ensureOutputBuffering()
    {
        if (ob_get_level() === 0) {
            ob_start();
        }
    }

    public function initializeSession()
    {
        if ($this->sessionStarted) {
            return;
        }

        if (! $this->sessionConfigured) {
            $this->configure();
            $this->sessionConfigured = true;
        }

        if (session_status() === PHP_SESSION_NONE) {
            try {
                session_start();
                $this->sessionStarted = true;
            } catch (RuntimeException $e) {
                throw new SessionInvalidException("Failed to start session", 0, $e);
            }
        }
    }

    /**
     * Configure session settings
     */
    protected function configure(): void
    {
        // Default session options
        $defaultOptions = [
            'lifetime' => 3600, // 1 hour
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'http_only' => true
        ];

        // Merge provided options with defaults
        $sessionOptions = array_merge($defaultOptions, $this->options);

        // Set PHP configuration options
        foreach ($sessionOptions as $key => $value) {
            if (str_starts_with($key, 'session.')) {
                ini_set($key, $value);
            }
        }

        // Configure session cookie parameters
        try {
            session_set_cookie_params(
                $sessionOptions['lifetime'],
                $sessionOptions['path'],
                $sessionOptions['domain'],
                $sessionOptions['secure'],
                $sessionOptions['http_only']
            );
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to set cookie parameters");
        }
    }

    /**
     * Get a session value with optional default
     * 
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Decrypted session value or default
     */
    public function get(string $key, $default = null)
    {
        $this->ensureSessionStarted();

        if (!$this->has($key)) {
            return $default;
        }

        try {
            return $this->unserialize($_SESSION[$key]);
        } catch (\Exception $e) {
            $this->clear();
            return $default;
        }
    }

    /**
     * Set a session value
     * 
     * @param string $key Session key
     * @param mixed $value Value to store
     * @throws EncryptionFailureException If encryption fails
     */
    public function set(string $key, $value): void
    {
        $this->ensureSessionStarted();

        try {
            $_SESSION[$key] = $this->serialize($value);
        } catch (EncryptionFailureException $e) {
            throw new EncryptionFailureException("Failed to encrypt $key.");
        }
    }

    /**
     * Check if a session key exists
     * 
     * @param string $key Session key to check
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->ensureSessionStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a specific session key
     * 
     * @param string $key Session key to remove
     */
    public function remove(string $key): void
    {
        $this->ensureSessionStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Clear entire session
     */
    public function clear(): void
    {
        $this->ensureSessionStarted();

        $_SESSION = [];

        // Expire session cookie if used
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session
        session_destroy();

        // Reset session started flag
        $this->sessionStarted = false;
    }

    /**
     * Get all session values
     * 
     * @return array Decrypted session values
     */
    public function all(): array
    {
        $this->ensureSessionStarted();

        return array_map(
            fn ($value) => $this->unserialize($value),
            $_SESSION
        );
    }

    /**
     * Regenerate session ID
     * 
     * @return bool True if regeneration successful
     */
    public function regenerate(): bool
    {
        $this->ensureSessionStarted();
        return session_regenerate_id(true);
    }

    /**
     * Ensure session is started before performing operations
     * 
     * @throws \RuntimeException If session cannot be started
     */
    private function ensureSessionStarted(): void
    {
        if (!$this->sessionStarted) {
            $this->initializeSession();
        }
    }
}
