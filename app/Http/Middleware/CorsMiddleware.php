<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
            $response->headers->set('Access-Control-Allow-Origin', '*'); // Specify your frontend origin here
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token, Authorization, Accept, charset, boundary, Confent-Length');
            $response->headers->set('Access-Control-Allow-Credentials', 'true'); // Allow credentials
            return $response;
        }

        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin', '*'); // Specify your frontend origin here
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token, Authorization, Accept, charset, boundary, Confent-Length');
        $response->headers->set('Access-Control-Allow-Credentials', 'true'); // Allow credentials
        return $response;
    }
}
