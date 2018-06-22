<?php

namespace Baselib;

/**
 * @desc
 * @package   Baselib
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright GPLv3
 */

class Utils{

    /**
     * 常用设置初始化函数
     *
     * @param array $settings 自定义设置，参见$defsettings
     * @return void
     */
    static public function baseInit($settings = []) {
        $defsettings = [
            'time_zone' => 'Asia/Chongqing',
            'memory_limit' => '2048M',
        ];
        $settings = array_merge($defsettings, $settings);
        date_default_timezone_set($settings['time_zone']);
        ini_set('memory_limit', $settings['memory_limit']);
    }

    /**
     * 生成随机数
     *
     * @param integer $length 随机数位数
     * @return 随机数的16进制字符串
     */
    static public function randomBytes($length = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
        return false;
    }

    /**
     * 字符串多功能定位和替换
     *
        查找所有img锚点，返回其中的内容：
            while(true) {
                $img = Baselib\Utils::stringPickup($allhtml, '<img', '>');
                if ($img === false) break;
            }
        只查找内容，不替换：
            $uid = Baselib\Utils::stringPickup($allhtml, '\"u\":\"','\"', false);
        返回所有匹配内容：
            $cntar = Baselib\Utils::stringPickup($input, 'src="', '"', 'array', true);
        查找所有img锚点，并通过回调函数处理：
            //例如把所有img改成lazycb方式
            //$replace 匹配内容，$begin 开始字符串，$end 结束字符串，$curpos 当前扫描到的位置
            //返回内容为该部分替换成的内容，返回 $begin.$replace.$end 为不作任何修改
            function lazycb($replace, $begin, $end, $curpos) {
                //<img src="data:img/jpg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEXs7Oxc9QatAAAACklEQVQI12NgAAAAAgAB4iG8MwAAAABJRU5ErkJggg==" lazy="loading" data-url="../cache/https/piccdn.igetget.com/img/201706/11/201706112052090188365283.jpg">
                if (strpos($replace, 'lazy="loading"') !== false) {
                    //避免lazyload加载时在后面加入@，导致文件找不到
                    $replace = str_replace(array('.jpg"','.png"'), array('.jpg#"','.png#"'), $replace);
                    //$orgurl = Baselib\Utils::stringPickup($replace, 'data-url="', '"', null);
                    //return $begin.' src="'.$orgurl.'"'.$end;
                }
                return $begin.$replace.$end;
            }
            Baselib\Utils::stringPickup($allhtml, $begin, $end, 'lazycb', true);
     *
     * @param string $str 需要处理的字符串内容
     * @param string $begin 开始的字符串
     * @param string $end 结束字符串
     * @param boolean $repl 匹配内容的处理方式 true：替换原文内容，false：不作任何修改，'array'：返回所有匹配内容（建议$recursive=true），callable：调用回调函数
     * @param boolean $recursive 是否递归处理所有
     * @param integer $offest 起始定位的偏移量
     * @return 找不到返回false，找到返回对应内容，递归查找返回true，$repl='array'时返回找到的数组
     */
    static public function stringPickup(&$str, $begin, $end = '', $repl = true, $recursive = false, $offest = 0)
    {
        $slen = strlen($begin);
        if ($slen == 0) {
            $spos = 0;
        } else {
            $spos = strpos($str, $begin, $offest);
        }
        if (false === $spos) {
            return false;
        }
        if ($end == '') {
            $epos = strlen($str);
        } else {
            $epos = strpos($str, $end, $spos+$slen);
        }
        if (false === $epos) {
            return false;
        }
        $ret = substr($str, $spos+$slen, $epos-$spos-$slen);
        if ($repl === true) {
            $replace = '';
        } else if ($repl === false) {
            $replace = $begin.$ret.$end;
        } else if ($repl === 'array'){
            $replace = $begin.$ret.$end;
            $ret = array($ret);
        } else {
            $replace = call_user_func_array($repl, array($ret, $begin, $end, $spos+$slen));
        }
        $nextpos = strlen($replace) + $spos;
        $str = substr($str, 0, $spos).$replace.substr($str, $epos + strlen($end));
        if ($recursive) {
            $subret = Utils::stringPickup($str, $begin, $end, $repl, $recursive, $nextpos);
            if ($repl === 'array') {
                if ($subret === false) {
                    return $ret;
                }
                return array_merge($ret, $subret);
            }
            return true;
        }
        return $ret;
    }
}
