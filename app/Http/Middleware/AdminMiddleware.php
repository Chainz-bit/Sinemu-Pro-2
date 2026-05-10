<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\ManagerPortal;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!ManagerPortal::check()) {
            return redirect()->route(ManagerPortal::loginRoute());
        }

        return $next($request);
    }
}
