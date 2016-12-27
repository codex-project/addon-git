<?php
/**
 * Part of the Codex Project packages.
 *
 * License and copyright information bundled with this package in the LICENSE file.
 *
 * @author    Robin Radic
 * @copyright Copyright 2016 (c) Codex Project
 * @license   http://codex-project.ninja/license The MIT License
 */
namespace Codex\Addon\Git\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Illuminate\Routing\Router;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    protected $excludedRouteNames = [
        'codex.hooks.git.webhook.github',

        'codex.hooks.git.webhook.bitbucket'
    ];

    /**
     * VerifyCsrfToken constructor.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param \Illuminate\Routing\Router                 $router
     */
    public function __construct(Encrypter $encrypter, Router $router)
    {
        parent::__construct($encrypter);
        $this->router = $router;
    }


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        if ($this->isReading($request) ||
            $this->shouldPassThrough($request) ||
            $this->tokensMatch($request) ||
            $this->excludedRouteNames($request)
        ) {
            return $this->addCookieToResponse($request, $next($request));
        }


        throw new TokenMismatchException;
    }

    protected function excludedRouteNames($request)
    {
        $routes = $this->router->getRoutes();

        foreach ($this->excludedRouteNames as $name) {
            if ($routes->hasNamedRoute($name)) {
                $route = $routes->getByName($name);
                if ($route->matches($request)) {
                    return true;
                }
            }
        }

        return false;
    }
}
