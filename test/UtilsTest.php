<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase {
    public function testBaseInit() {
        Baselib\Utils::baseInit(['time_zone'=>'Asia/Chongqing','memory_limit'=>'1000M']);
        //try if can allow 250M string
        $key = '';
        for($i=0;$i<1000000;$i++) {
            $key .= '0';
        }
        $bigstr = '';
        for($i=0;$i<250;$i++) {
            $bigstr .= $key;
        }
        $bigstr = '';
        $this->assertTrue(strtotime('2018-06-21 00:00:00') === 1529510400);
    }
    public function testRandomBytes() {
        $rand = Baselib\Utils::randomBytes(4);
        $this->assertEquals(strlen($rand), 8);
    }
    public function _replacecb($replace, $begin, $end, $curpos) {
        $replace = str_replace('jpg', 'png', $replace);
        return $begin.$replace.$end;
    }
    public function testStringPickup() {
        $orgstr = 's <img src="http://test.com/a.jpg" /> <img src="http://test.com/b.jpg" /> <img src="http://test.com/c.jpg" /> e';
        $input = $orgstr;
        $inputlast = 's    e';
        $output = '["http:\/\/test.com\/a.jpg","http:\/\/test.com\/b.jpg","http:\/\/test.com\/c.jpg"]';
        $ret1 = array();
        while(true) {
            $ret = Baselib\Utils::stringPickup($input, '<img', '>', true);
            if ($ret === false) break;
            $ret = Baselib\Utils::stringPickup($ret, 'src="','"', false);
            if ($ret === false) continue;
            $ret1[] = $ret;
        }
        $this->assertEquals(json_encode($ret1), $output);
        $this->assertEquals($input, $inputlast);
        $input = $orgstr;
        $ret2 = Baselib\Utils::stringPickup($input, 'src="', '"', 'array', true);
        $this->assertEquals(json_encode($ret1), json_encode($ret2));
        $this->assertEquals($input, $orgstr);
        $output = 's <img src="http://test.com/a.png" /> <img src="http://test.com/b.png" /> <img src="http://test.com/c.png" /> e';
        Baselib\Utils::stringPickup($input, '<img', '>', array($this,'_replacecb'), true);
        $this->assertEquals($input, $output);
        $output = 'http://test.com/b.png';
        $ret = Baselib\Utils::stringPickup($input, 'src="','"', false, false, 20);
        $this->assertEquals($ret, $output);
    }
    public function testAsyncExecute() {
        $start = microtime(true);
        Baselib\Utils::asyncExec('sleep 1');
        $end = microtime(true);
        $this->assertLessThan(0.5, $end - $start);
    }
}
