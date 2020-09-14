<?php
/**
 * Response Helper Library
 * User: zng
 * Date: 2020/7/6
 * Time: 17:35
 */
use Symfony\Component\HttpFoundation\Response;

if ( ! function_exists('stored') ) {
    /**
     * 创建资源成功后响应
     *
     * @param mixed $data 参数
     * @param string $message 消息
     * @return \Illuminate\Http\JsonResponse
     */
    function stored( $data=[], string $message='创建成功' )
    {
        return respond($data, $message);
    }
}

if ( ! function_exists('updated') ) {
    /**
     * 更新资源成功后响应
     *
     * @param mixed $data 参数
     * @param string $message 消息
     * @return \Illuminate\Http\JsonResponse
     */
    function updated( $data=[], string $message='更新成功' )
    {
        return respond($data, $message);
    }
}

if ( ! function_exists('deleted') ) {
    /**
     * 删除资源后成功响应
     *
     * @param string $message 消息
     * @return \Illuminate\Http\JsonResponse
     */
    function deleted( string $message='删除成功' )
    {
        return message($message);
    }
}

if ( ! function_exists('accepted') ) {
    /**
     * 请求已被放入任务队列响应
     *
     * @param string $message 消息
     * @return \Illuminate\Http\JsonResponse
     */
    function accepted(string $message='请求已被接收,请等待处理')
    {
        return message($message, Response::HTTP_ACCEPTED);
    }
}

if ( ! function_exists('notFound') ) {
    /**
     * 资源不存在响应
     *
     * @param string $message 消息
     * @return \Illuminate\Http\JsonResponse
     */
    function notFound(string $message='您访问的资源不存在')
    {
        return message($message, Response::HTTP_NOT_FOUND);
    }
}

if ( ! function_exists('internalError') ) {
    /**
     * 服务端未知错误响应
     *
     * @param string $message 消息
     * @param int $code 状态码
     * @return \Illuminate\Http\JsonResponse
     */
    function internalError( string $message='未知错误导致请求失败', int $code=Response::HTTP_INTERNAL_SERVER_ERROR )
    {
        return message($message, $code);
    }
}

if ( ! function_exists('failed') ) {
    /**
     * 错误的请求响应
     *
     * @param string $message 消息
     * @param int $code 状态码
     * @return \Illuminate\Http\JsonResponse
     */
    function failed( string $message, int $code=Response::HTTP_BAD_REQUEST )
    {
        return message($message, $code);
    }
}

if ( ! function_exists('success') ) {
    /**
     * 请求成功响应
     *
     * @param mixed $data 参数
     * @return \Illuminate\Http\JsonResponse
     */
    function success($data=[])
    {
        return respond($data);
    }
}

if ( ! function_exists('message') ) {
    /**
     * 消息响应
     *
     * @param string $message 消息
     * @param int $code 状态码
     * @return \Illuminate\Http\JsonResponse
     */
    function message( string $message, int $code=Response::HTTP_OK )
    {
        return respond([], $message, $code);
    }
}

if ( ! function_exists('respond') ) {
    /**
     * 生成响应体
     *
     * @param mixed $data 参数
     * @param string $message 消息
     * @param int $code 状态码
     * @param array $headers 响应头
     * @return \Illuminate\Http\JsonResponse
     */
    function respond( $data=[], string $message='请求成功', int $code=Response::HTTP_OK, array $headers=[] ) {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code, $headers);
    }
}