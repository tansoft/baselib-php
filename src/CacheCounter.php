<?php

namespace Baselib;

/**
 * @desc
 * @package   Baselib
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright GPLv3
 */

define('CACHECOUNTER_PREFIX', 'cccnt'); ///<该计数器最新总数
define('CACHELASTTS_PREFIX', 'cclts');  ///<该计数器的最后更新时间
define('CACHELASTDAYCNT_PREFIX', 'ccldc');  ///<该计数器上一天的计数总数
define('CACHEDAYST_PREFIX', 'ccdys');   ///<该计数器当天更新最早时间
define('CACHEINFO_PREFIX', 'ccinf');    ///<该计数器相关计数信息
define('MAXSAVE_DATE', 5);              ///<汇总信息里只保留最近7天数据

/*
    简单计数器实现，支持简单的汇总统计
    增加计数：
        $counter->add('sthkey');
    读取计数：
        $cnt = $counter->get('sthkey');
    获取最后更新时间：
        $lasttimestamp = CacheCounter::getLastUpdateTime('sthkey');
    获取今天的计数值：
        $todaycnt = CacheCounter::getToday('sthkey');
    获取今天的计数详情：
        $info = CacheCounter::getTodayInfo('sthkey');
        //sum day st et
*/
class CacheCounter
{
    private $cache;
    //use Utils::getCacheInstance to get cache
    public function __construct($cache) {
        $this->cache = $cache;
    }
    public function get($name) {
        return $this->cache->get(CACHECOUNTER_PREFIX.$name, 0);
    }
    public function getLastUpdateTime($name) {
        return $this->cache->get(CACHELASTTS_PREFIX.$name, 0);
    }
    public function getToday($name) {
        $curkey = CACHECOUNTER_PREFIX.$name;
        $lastkey = CACHELASTDAYCNT_PREFIX.$name;
        $lasttskey = CACHELASTTS_PREFIX.$name;
        $ret = $this->cache->getMultiple(array($curkey, $lastkey, $lasttskey), 0);
        @$lastts = intval($ret[$lasttskey]);
        $curday = strtotime(date("Y-m-d"), time());
        //echo($lastts.','.$curday.':');
        if ($lastts < $curday) return 0;
        @$cnt = intval($ret[$curkey]) - intval($ret[$lastkey]);
        return $cnt;
        //return intval(Cache::get(CACHECOUNTER_PREFIX.$name)) - intval(Cache::get(CACHELASTDAYCNT_PREFIX.$name));
    }
    public function getTodayInfo($name) {
        $curcntkey = CACHECOUNTER_PREFIX.$name;
        $lastdaycntkey = CACHELASTDAYCNT_PREFIX.$name;
        $earlytskey = CACHEDAYST_PREFIX.$name;
        $lasttskey = CACHELASTTS_PREFIX.$name;
        $ret = $this->cache->getMultiple(array($curcntkey, $lastdaycntkey, $earlytskey, $lasttskey), 0);
        @$curcnt = intval($ret[$curcntkey]);
        @$lastdaycnt = intval($ret[$lastdaycntkey]);
        @$earlyts = intval($ret[$earlytskey]);
        @$lastts = intval($ret[$lasttskey]);
        $lastcnt = $curcnt-$lastdaycnt;
        return array('sum'=>$curcnt, 'day'=>$lastcnt, 'st'=>$earlyts, 'et'=>$lastts);
    }
    //$day = '2018-06-02'
    public function getLastInfoByDay($name, $day) {
        $todayts = strtotime(date("Y-m-d"));
        $ts = strtotime($day);
        if ($todayts == $ts) return $this->getTodayInfo($name);
        $info = $this->cache->get(CACHEINFO_PREFIX.$name);
        if (!empty($info)) {
            if (isset($info[$ts])) {
                $info = $info[$ts];
            } else {
                $info = null;
            }
        }
        return $info;
    }
    /*
        $info = [
            1470844800=>['sum'=>13,'day'=>5,'st'=>1470844800,'et'=>1470845900],
            1470931200=>['sum'=>8,'day'=>8,'st'=>1470931400,'et'=>1470933500],
        ]
    */
    public function getLastInfo($name) {
        $ts = strtotime(date("Y-m-d",time()+86400));
        $today = $this->getTodayInfo($name);
        $info = $this->cache->get(CACHEINFO_PREFIX.$name);
        if (empty($info)) $info = array();
        $info[$ts] = $today;
        return $info;
    }
    public function set($name, $cnt) {
        $this->_perParse($name);
        return $this->cache->set(CACHECOUNTER_PREFIX.$name, $cnt, 0);
    }
    public function add($name, $cnt=1) {
        $this->_perParse($name);
        return $this->cache->add(CACHECOUNTER_PREFIX.$name, $cnt, 0);
    }
    public function touch($name) {
        $this->_perParse($name);
    }
    protected function _perParse($name) {
        $lastts = $this->cache->get(CACHELASTTS_PREFIX.$name, 0);
        $curts = time();
        //先更新减少跨天reset的冲突
        $this->cache->set(CACHELASTTS_PREFIX.$name, $curts, 0);
        $curday = strtotime(date("Y-m-d"), $curts);
        if ($lastts != 0 && $lastts<$curday) {
            //new day
            $curcntkey = CACHECOUNTER_PREFIX.$name;
            $lastdaycntkey = CACHELASTDAYCNT_PREFIX.$name;
            $earlytskey = CACHEDAYST_PREFIX.$name;
            $infokey = CACHEINFO_PREFIX.$name;
            $ret = $this->cache->getMultiple(array($curcntkey, $lastdaycntkey, $earlytskey, $infokey), 0);
            @$lastdaycnt = intval($ret[$lastdaycntkey]);
            @$earlyts = intval($ret[$earlytskey]);
            @$curcnt = intval($ret[$curcntkey]);
            $info = isset($ret[$infokey])?$ret[$infokey]:null;
            $lastday = strtotime(date("Y-m-d"),$lastts);
            $lastcnt = $curcnt-$lastdaycnt;
            if (empty($info)) $info = array();
            if ($lastcnt > 0 || $earlyts>= $lastday) {
                if ($earlyts == 0) $earlyts = $lastday;
                $info[$lastday] = array('sum'=>$curcnt, 'day'=>$lastcnt, 'st'=>$earlyts, 'et'=>$lastts);
            }
            //移除过旧的数据
            $minday = $curday - MAXSAVE_DATE * 86400;
            foreach($info as $ts=>$inf) {
                if ($ts < $minday) unset($info[$ts]);
            }
            $this->cache->setMultiple(array(
                $lastdaycntkey => $curcnt,
                $infokey => $info,
                $earlytskey => $curts));
        }
    }
    public function getStatusHtml($name) {
        $ret = '';
        $last = $this->getLastInfo($name);
        foreach($last as $ts=>$obj) {
            $ret .= '<strong>'.date('Y-m-d', $ts).'：</strong>总计：'.$obj['sum'].'，当天：'.$obj['day'].'，范围：'.date('H:i:s',$obj['st']).'~'.date('H:i:s',$obj['et']).'<br>';
        }
        return $ret;
    }
    public function getStatusTooltipJs() {
        return '<script>
            $(".counteritem").mouseover(function(){
                $(this).prev().css("display","block").css("left",event.layerX).css("top",event.layerY+10).css("z-index",999);
            });
            $(".counteritem").mouseout(function(){
                $(this).prev().css("display","none");
            });
            </script>';
    }
    //warninglimit = 0 不报警
    public function getStatusRealTimeText($name, $warninglimit = 60, $today = true, $format = 'html', $skip0 = false) {
        $tskey = CACHELASTTS_PREFIX.$name;
        $totalkey = CACHECOUNTER_PREFIX.$name;
        $lastdaycntkey = CACHELASTDAYCNT_PREFIX.$name;
        $readkey = array($tskey, $totalkey);
        if ($today) $readkey[] = $lastdaycntkey;
        $ret = $this->cache->getMultiple($readkey, 0);
        @$ts = intval($ret[$tskey]);
        @$total = intval($ret[$totalkey]);
        if ($today) {
            @$total -= intval($ret[$lastdaycntkey]);
        }
        if ($total == 0 && $skip0) return '';
        $curts = time();
        $ret = '';
        if ($format == 'html') {
            $ret .= '<div style="position:absolute;white-space:nowrap;display:none;font-size:9pt;color:#fff;background-color:#31b0d5;border-color:#269abc;border:1px solid transparent;border-radius:4px;padding:15px;">'
                .$this->getStatusHtml($name).'</div>';
            $ret .= '<div class="counteritem" data-name="'.$name.'">';
            if ($warninglimit && $curts > $ts + $warninglimit) {
                $ret .= '<font color="red">'.$total.' <h4 style="display:inline-block;">('.date('H:i', $ts).')</h4></font>';
            } else {
                $ret .= '<font color="green">'.$total.'</font>';
            }
            $ret .= '</div>';
            $ret .= '';
        } else if ($format == 'monitor') {
            if ($warninglimit && $curts > $ts + $warninglimit) {
                $ret = $total.' ('.date('H:i', $ts).')';
            }
        }
        return $ret;
    }
    public static function setNames($auditname = '') {
        if (empty($auditname)) {
            global $login_info;
            if (isset($login_info['admin_name'])) {
                $auditname = $login_info['admin_name'];
            } else {
                $auditname = '内网用户';
            }
        }
        //Cache::setNames($auditname);
        return $auditname;
    }
}
