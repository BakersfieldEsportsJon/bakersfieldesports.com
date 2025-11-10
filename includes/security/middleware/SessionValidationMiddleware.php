<?php
namespace Security\Middleware;

use Security\SessionManager;
use Security\Exceptions\SessionSecurityException;
use Security\Exceptions\SessionTimeoutException;

class SessionValidationMiddleware
{
    protected $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function __invoke($request, $response, $next)
    {
        try {
            // Validate session
            if (!$this->sessionManager->isValid()) {
                throw new SessionSecurityException('Invalid session');
            }

            // Check session timeout
            if ($this->sessionManager->isExpired()) {
                throw new SessionTimeoutException('Session expired');
            }

            // Update last activity timestamp
            $this->sessionManager->updateLastActivity();

            return $next($request, $response);
        } catch (SessionSecurityException | SessionTimeoutException $e) {
            // Log security event
            $this->sessionManager->logSecurityEvent($e);

            // Clear session and redirect to login
            $this->sessionManager->destroy();
            return $response->withRedirect('/admin/login');
        }
    }
}
