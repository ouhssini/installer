<?php

namespace SoftCortex\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use SoftCortex\Installer\Services\InstallerService;

class EnsureInstalled
{
    public function __construct(
        private InstallerService $installer
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if route is installer route
        if ($request->is('install*')) {
            return $next($request);
        }

        // Check installation status
        if (!$this->installer->isInstalled()) {
            return redirect()->route('installer.welcome');
        }

        return $next($request);
    }
}
