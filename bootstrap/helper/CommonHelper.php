<?php
/**
 * Helper Library
 * User: zng
 * Date: 2020/7/6
 * Time: 17:25
 */
use Illuminate\Support\Facades\Route;

if ( ! function_exists('curl_req') ) {
    /**
     * CURL请求
     * @param string $url 地址
     * @param array $params 参数
     * @param string $method 请求方式 GET\POST\DELETE\PUT
     * @param array $header 请求头
     * @param int $timeout 超时时间
     * @throws \App\Exceptions\OrdinaryException|\Illuminate\Http\Client\RequestException
     * @return mixed
     */
    function curl_req( string $url, array $params=[], $method="GET", array $header=[], $timeout = 30 )
    {
        if ( ! in_array(strtoupper($method), ['GET','POST','DELETE','PUT']) ) {
            throw new \App\Exceptions\OrdinaryException('错误的请求方式');
        }
        $http = \Illuminate\Support\Facades\Http::withHeaders($header)->timeout($timeout);
        if ( strtoupper($method) === 'POST' && $header['Content-Type'] === 'application/x-www-form-urlencoded' ) {
            $http = $http->asForm();
        }
        $req_mode = strtolower($method);

        return $http->$req_mode( $url, $params )->throw()->json();
    }
}

if ( ! function_exists('route_class') ) {
    /**
     * 返回路由类名
     * @return mixed
     */
    function route_class()
    {
        return str_replace('.', '-', Route::currentRouteName());
    }
}

if ( ! function_exists('big_number') ) {
    /**
     * 返回并初始化 BigNumber 类
     * @param $number
     * @param int $scale
     * @return \Moontoast\Math\BigNumber
     */
    function big_number($number, $scale=2)
    {
        return new \Moontoast\Math\BigNumber($number, $scale);
    }
}