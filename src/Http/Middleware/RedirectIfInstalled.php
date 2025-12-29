<?php

namespace SoftCortex\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use SoftCortex\Installer\Services\InstallerService;

class RedirectIfInstalled
{
    public function __construct(
        private InstallerService $installer
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installer->isInstalled()) {
            $redirectRoute = config('installer.routes.redirect_after_install', 'dashboard');
            
            // Try to redirect to configured route, fallback to home
            try {
                return redirect()->route($redirectRoute);
            } catch (\Exception $e) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
