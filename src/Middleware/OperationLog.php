<?php

namespace Kevinbai\Admin\Middleware;

use Kevinbai\Admin\Facades\Admin;
use Illuminate\Http\Request;

class OperationLog
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if (Admin::user()) {
            $log = [
                'user_id' => Admin::user()->id,
                'path'    => $request->path(),
                'method'  => $request->method(),
                'ip'      => $request->getClientIp(),
                'input'   => json_encode($request->input()),
            ];

            \Kevinbai\Admin\Auth\Database\OperationLog::create($log);
        }

        return $next($request);
    }
}
