<?php

namespace App\Http\Middleware;

use App\Exceptions\InvalidRequestException;
use Closure;

class RandomDropSeckillRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @throws InvalidRequestException
     * @throws \Exception
     * @return mixed
     */
    public function handle($request, Closure $next, $percent)
    {
        if ( random_int(0, 100) < (int)$percent ) {
            throw new InvalidRequestException('参与用户数过多, 请稍后再试', 403);
        }

        return $next($request);
    }
}
