<?php

namespace Rose\Http\Middleware;

use Closure;
use Rose\Contracts\Routing\Middleware\Middleware as MiddlewareContract;
use Rose\Contracts\Session\Storage as SessionStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class CsrfMiddleware implements MiddlewareContract
{
    /**
     * The session storage instance.
     *
     * @var SessionStorage
     */
    protected $session;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [];

    /**
     * The HTTP methods that don't require CSRF verification.
     *
     * @var array
     */
    protected $excludedMethods = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * The name of the CSRF token session key.
     *
     * @var string
     */
    protected $sessionKey = 'csrf_token';

    /**
     * The name of the CSRF token field in forms.
     *
     * @var string
     */
    protected $tokenField = '_token';

    /**
     * The name of the CSRF token header.
     *
     * @var string
     */
    protected $headerName = 'X-CSRF-TOKEN';

    /**
     * The HTMX-specific CSRF header name.
     * HTMX sends the CSRF token in this header by default when configured.
     *
     * @var string
     */
    protected $htmxHeaderName = 'X-HX-CSRF-Token';

    /**
     * Cookie settings
     * 
     * @var array
     */
    protected $cookie = [
        'name' => 'XSRF-TOKEN',
        'lifetime' => 120, // minutes
        'sameSite' => 'lax'
    ];

    /**
     * Create a new CSRF middleware instance.
     *
     * @param SessionStorage $session
     * @param array $options
     */
    public function __construct(SessionStorage $session, array $options = [])
    {
        $this->session = $session;

        if (isset($options['except'])) {
            $this->except = $options['except'];
        }

        if (isset($options['excludedMethods'])) {
            $this->excludedMethods = $options['excludedMethods'];
        }

        if (isset($options['sessionKey'])) {
            $this->sessionKey = $options['sessionKey'];
        }

        if (isset($options['tokenField'])) {
            $this->tokenField = $options['tokenField'];
        }

        if (isset($options['headerName'])) {
            $this->headerName = $options['headerName'];
        }

        if (isset($options['htmxHeaderName'])) {
            $this->htmxHeaderName = $options['htmxHeaderName'];
        }

        if (isset($options['cookie'])) {
            $this->cookie = array_merge($this->cookie, $options['cookie']);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     * 
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a CSRF token if one doesn't exist in the session
        $this->ensureTokenExists();

        // Skip validation for excluded methods or paths
        if (
            $this->isReading($request) ||
            $this->inExceptArray($request) ||
            $this->tokensMatch($request)
        ) {
            $response = $next($request);
            
            // Add CSRF cookie to the response
            $response = $this->addCsrfCookie($response);
            
            // For HTMX boosted requests, also add the CSRF token as a header
            // This helps with partial page updates 
            if ($this->isHtmxRequest($request)) {
                $response->headers->set('X-CSRF-Token', $this->getTokenFromSession());
            }
            
            return $response;
        }

        // Token mismatch
        if ($this->isHtmxRequest($request)) {
            // For HTMX requests, return a 422 status with HX-Retarget
            // This allows HTMX to handle the error gracefully
            $response = new Response('CSRF token mismatch', 422);
            $response->headers->set('HX-Retarget', 'body');
            return $response;
        }

        // For regular requests, throw an exception
        throw new \Exception('CSRF token mismatch', 419);
    }

    /**
     * Determine if the request is an HTMX request.
     *
     * @param Request $request
     * @return bool
     */
    protected function isHtmxRequest(Request $request): bool
    {
        return $request->headers->has('HX-Request');
    }

    /**
     * Determine if the HTTP request uses a 'read' verb.
     *
     * @param Request $request
     * @return bool
     */
    protected function isReading(Request $request): bool
    {
        return in_array($request->getMethod(), $this->excludedMethods);
    }

    /**
     * Determine if the request URI is in the excluded list.
     *
     * @param Request $request
     * @return bool
     */
    protected function inExceptArray(Request $request): bool
    {
        $path = $request->getPathInfo();

        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($path === $except) {
                return true;
            }

            $pattern = preg_quote($except, '#');
            
            // Handle wildcard patterns like 'api/*'
            $pattern = str_replace('\*', '.*', $pattern);
            
            if (preg_match('#^' . $pattern . '#', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param Request $request
     * @return bool
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromSession();

        // Check for token in request
        $requestToken = $this->getTokenFromRequest($request);

        if (!$token || !$requestToken) {
            return false;
        }

        return hash_equals($token, $requestToken);
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // First check HTMX-specific header
        $token = $request->headers->get($this->htmxHeaderName);
        
        // Then check form input
        if (!$token) {
            $token = $request->request->get($this->tokenField);
        }

        // Check standard header
        if (!$token) {
            $token = $request->headers->get($this->headerName);
        }

        // Check X-XSRF-TOKEN header
        if (!$token && $header = $request->headers->get('X-XSRF-TOKEN')) {
            $token = urldecode($header);
        }

        return $token;
    }

    /**
     * Get the CSRF token from the session.
     *
     * @return string|null
     */
    protected function getTokenFromSession(): ?string
    {
        return $this->session->get($this->sessionKey);
    }

    /**
     * Ensure the CSRF token exists in the session.
     *
     * @return void
     */
    protected function ensureTokenExists(): void
    {
        if ($this->session->has($this->sessionKey)) {
            return;
        }

        $this->session->set($this->sessionKey, $this->generateNewToken());
    }

    /**
     * Generate a new CSRF token.
     *
     * @return string
     */
    protected function generateNewToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Add the CSRF token to the response cookies.
     *
     * @param Response $response
     * @return Response
     */
    protected function addCsrfCookie(Response $response): Response
    {
        $token = $this->getTokenFromSession();
        
        // Get cookie lifetime in seconds
        $lifetime = $this->cookie['lifetime'] * 60;

        // Add CSRF token as a cookie for JavaScript frameworks
        $response->headers->setCookie(new Cookie(
            $this->cookie['name'],
            $token,
            time() + $lifetime,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            $this->cookie['sameSite'] ?? 'lax'
        ));

        return $response;
    }

    /**
     * Get the current CSRF token.
     *
     * @return string
     */
    public function getToken(): string
    {
        $this->ensureTokenExists();
        return $this->getTokenFromSession();
    }

    /**
     * Refresh the CSRF token in the session.
     *
     * @return string
     */
    public function refreshToken(): string
    {
        $token = $this->generateNewToken();
        $this->session->set($this->sessionKey, $token);
        return $token;
    }
}
