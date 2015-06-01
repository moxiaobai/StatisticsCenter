<?php

/**
 * 统计客户端
 *
 * @example
 * $instance = StatisticClient::instance('192.168.1.202');
 * $instance->tick();
 * $instance->report('User', 'getInfo', true, 200, '处理成功')
 *
 * @author: moxiaobai
 * @since : 2015/5/30 13:35
 */

class StatisticClient {

    private static $_instances = array();

    protected $reportAddress = NULL;
    protected $starTime      = NULL;

    /**
     * @param $reportAddress
     * @return StatisticClient
     */
    public static function instance($reportAddress) {
        if ( ! isset(self::$_instances[$reportAddress]) ) {
            $instance = new StatisticClient($reportAddress);
            self::$_instances[$reportAddress] = $instance;

            return $instance;

        }
        return self::$_instances[$reportAddress];
    }

    /**
     * @param $reportAddress 服务器上报地址
     */
    public function __construct($reportAddress) {
        $this->reportAddress = $reportAddress;
    }

    /**
     * 模块接口上报消耗时间记时
     *
     * @return void
     */
    public function tick() {
        $this->starTime = $this->microtimeFloat();
    }

    /**
     * 上报统计数据
     * @param string $module
     * @param string $interface
     * @param bool $success
     * @param int $code
     * @param string $report_address
     * @return boolean
     */
    public function report($module, $interface, $success, $msg) {
        //消耗时间
        $costTime = $this->microtimeFloat() - $this->starTime;

        $data =array(
            'module'     => $module,
            'interface'  => $interface,
            'costTime'   => $costTime,
            'success'    => $success,
            'msg'        => $msg
        );

        $buffer = json_encode($data) . "\r\n\r\n";
        return $this->sendData($this->reportAddress, $buffer);
    }

    /**
     * 发送数据给统计系统
     * @param string $address
     * @param string $buffer
     * @return boolean
     */
    private function sendData($address, $buffer) {
        //判断是否存在swoole_client方法
        if (!extension_loaded('swoole')) {
            $fp = stream_socket_client("udp://{$address}", $errno, $errstr, 30);
            if (!$fp) {
                throw new Exception("$errstr ($errno)");
            }

            fwrite($fp, $buffer);
            $ret = fread($fp, 1024);
            fclose($fp);

            return $ret;
        } else {
            $client = new swoole_client(SWOOLE_SOCK_UDP);

            $address = explode(':', $address);
            $ret = $client->connect($address[0], $address[1], 0.5);
            if(!$ret) {
                throw new Exception($client->errCode);
            }

            $client->send($buffer);
            $ret =  $client->recv();
            $client->close();

            return $ret;
        }
    }

    private function microtimeFloat() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

}

$instance = StatisticClient::instance('192.168.1.202:9603');
$instance->tick();
$instance->report('User', 'setPassword', false, '处理成功');