<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
        AuthenticationException::class,
        AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        OrdinaryException::class,
        InvalidRequestException::class,
        CouponCodeUnavailableException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ( $request->expectsJson() ) {
            if ( ( $json = $this->json( $exception ) ) instanceof JsonResponse ) {
                return $json;
            }
        } else {
            $this->view( $exception );
        }

        return parent::render($request, $exception);
    }

    /**
     * json响应异常
     * @param Throwable $exception
     * @author zx
     * @date 2020-06-22
     */
    private function json( $exception )
    {
        // 拦截普通异常并响应
        if ( $exception instanceof OrdinaryException ) {
            return failed($exception->getMessage(), $exception->getCode()?:Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        // 拦截404异常并响应
        if ( $exception instanceof ModelNotFoundException ) {
            return notFound();
        }
        // 拦截身份认证异常
        if ( $exception instanceof AuthenticationException ) {
            return failed('您的身份认证失败', Response::HTTP_UNAUTHORIZED);
        }
        // 拦截授权异常
        if ( $exception instanceof AuthorizationException ) {
            return failed('您无权访问', Response::HTTP_FORBIDDEN);
        }
        // 拦截参数验证错误异常
//        if ( $exception instanceof ValidationException ) {
//            return failed(Arr::first(Arr::collapse($exception->errors())), Response::HTTP_UNPROCESSABLE_ENTITY);
//        }
        // 拦截用户认证异常
        if ( $exception instanceof UnauthorizedHttpException ) {
            return failed('未提供TOKEN', Response::HTTP_UNAUTHORIZED);
        }
        return false;
    }

    /**
     * view响应异常
     * @param Throwable $exception
     * @author zx
     * @date 2020-06-22
     */
    private function view( $exception )
    {
        // 拦截普通异常并响应
        if ( $exception instanceof OrdinaryException ) {
            abort(\Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
        }
        // 拦截404异常并响应
        if ( $exception instanceof ModelNotFoundException ) {
            abort(\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND, '您访问的资源不存在');
        }
        // 拦截身份认证异常
        if ( $exception instanceof AuthenticationException ) {
            abort(\Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN, '身份异常');
        }
        // 拦截授权异常
        if ( $exception instanceof AuthorizationException ) {
            abort(\Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED, '您无权访问');
        }
        // 拦截用户认证异常
        if ( $exception instanceof UnauthorizedHttpException ) {
            abort(\Symfony\Component\HttpFoundation\Response::HTTP_PRECONDITION_FAILED, '未提供有效令牌');
        }
    }

}
