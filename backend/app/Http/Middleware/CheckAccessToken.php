<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    private function isValidCredentials($token, $request)
    {
        (int) $id = $request->route('id');
        $user = User::where('id', $id)->where('token', $token)->first();

       if($user){
           return true;
       }
        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {

        if (!$request->hasHeader('Authorization')) {
            return response()->json(['err' => 'Unauthorized'], 401);
        }

        $token = $request->bearerToken();

        if (!$this->isValidCredentials($token, $request)) {
            return response()->json(['err' => 'Invalid Credentials'], 401);
        }

        return $next($request);
    }
}
