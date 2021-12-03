<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class CheckJWTClaims extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     * @param null $role
     * @return mixed
     */
    public function handle($request, Closure $next, $role = null)
    {
        // try {
        //     // 解析token角色
        //     $token_id = $this->auth->parseToken()->getClaim('role');
        //     $token_email = $this->auth->parseToken()->getClaim('email');
        //     $token_role = $this->auth->parseToken()->getClaim('role');
        //     $token_company_id = $this->auth->parseToken()->getClaim('company_id');
        //     $token_company_type = $this->auth->parseToken()->getClaim('company_type');
        // } catch (JWTException $e) {
        //     /**
        //      * token解析失敗，說明請求中沒有可用的token。
        //      * 為了可以全域性使用（不需要token的請求也可通過），這裡讓請求繼續。
        //      * 因為這個中介軟體的責職只是校驗token裡的角色。
        //      */
        //     return $next($request);
        // }

        // // 判斷token角色。
        // if ($token_role != $role) {
        //     throw new UnauthorizedHttpException('jwt-auth', 'User role error');
        // }

        // return $next($request);
    }
}
