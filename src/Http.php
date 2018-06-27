<?php

namespace Baselib;

/**
 * @desc
 * @package   Baselib
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright GPLv3
 */

class Http{
    /**
     * 发起请求
     *
     * @param string $url 请求url
     * @param array or string $params 请求参数，如果是数组形式，使用http_build_query处理，如果是字符串，直接追加
     * @param boolean $ispost 是否为post请求，默认get
     * @param string $referer Referer
     * @param string $useragent UserAgent
     * @param headers $headers 额外的http头，如：['Content-type: text/plain', 'Content-length: 100']
     * @param integer $timeout 超时时间
     * @param boolean $returnjson 返回是否为json，默认是
     * @param string $cookiefile 上下文cookie保存文件
     * @return 随机数的16进制字符串
     */
    static public function curl($url, $params = array(), $ispost = false, $returnjson = true, $referer = '', $useragent = '', $headers = array(), $timeout = 30, $cookiefile = null){
        $ch = curl_init();
        if (is_array($params)) {
            $params = http_build_query($params);
        }
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            if (!empty($params)) {
                if (strpos($url, '?') === false) {
                    $url .= '?'.$params;
                } else {
                    $url .= '&'.$params;
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if (empty($useragent)) {
            $useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36';
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($cookiefile)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);//用于请求时带上cookie
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);//用于返回cookie更新
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $handles = curl_exec($ch);
        curl_close($ch);
        if ($returnjson && $handles) {
            $handles = json_decode($handles, true);
        }
        return $handles;
    }
}