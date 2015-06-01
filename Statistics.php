<?php

/**
 * Api数据统计
 *
 * @author: moxiaobai
 * @since : 2015/5/30 11:08
 */

require_once __DIR__ . '/Library/Autoloader.php';

use Service\Logger;
use Service\Alarm;

class Statistics {

    private $_server;

    public function __construct($cmd) {
        if($cmd == 'start') {
            $this->_server = new swoole_server("0.0.0.0", 9603);
            $this->_server->addlistener("0.0.0.0", 9603, SWOOLE_SOCK_UDP);
            $this->_server->set(array(
                'worker_num'      => 4,
                'task_worker_num' => 4,
                'dispatch_mode'   => 3,
                'max_request'     => 10000,
                'open_eof_check'  => true,        //打开EOF检测
                'package_eof'     => "\r\n\r\n", //设置EOF
                'open_eof_split'  => true,        //启用EOF自动分包
                'debug_mode'      => 1 ,
                'daemonize'       => 1,
                'log_file'        => __DIR__ . '/Log/swoole.log'
            ));

            //增加监听的端口
            $this->_server->addlistener("127.0.0.1", 9604, SWOOLE_SOCK_UDP);

            //设置事件回调
            $this->_server->on('Start', array($this, 'onStart'));
            $this->_server->on('WorkerStart', array($this, 'onWorkerStart'));
            $this->_server->on('Receive', array($this, 'onReceive'));
            $this->_server->on('Task', array($this, 'onTask'));
            $this->_server->on('Finish', array($this, 'onFinish'));
            $this->_server->on('Shutdown', array($this, 'onShutdown'));

            $this->_server->start();
        } else {
            $this->manage($cmd);
        }
    }

    //主进程的主线程回调此函数
    public function onStart(swoole_server $server) {
        echo '服务器启动: ' . date('Y-m-d H:i:s') . PHP_EOL;

        //设置主进程名称
        swoole_set_process_name('Statistics');
    }

    //worker启动，初始化任务
    public function onWorkerStart(swoole_server $server , $worker_id) {}

    /**
     * 对外提供接口
     *
     * @param  $server      swoole_server对象
     * @param $fd           TCP客户端连接的文件描述符
     * @param $from_id      TCP连接所在的Reactor线程ID
     * @param $data         收到的数据内容
     */
    public function onReceive(swoole_server $server, $fd, $from_id, $data) {

        if(empty($data)) {
            // 发送数据给客户端，请求包错误
            $data = array('code'=>500, 'msg'=>'非法请求', 'data'=>null);
            $server->send($fd, json_encode($data));
        }

        //局域网管理
        $udpClient = $server->connection_info($fd, $from_id);
        if($udpClient['server_port'] == '9604') {
            echo $data . PHP_EOL;

            switch($data) {
                case 'stop':
                    echo '服务器关闭: ' . date('Y-m-d H:i:s') . PHP_EOL;

                    $server->shutdown();
                    $server->send($fd, '服务器关闭成功');

                    break;
                case 'reload':
                    echo 'Worker进程重启: ' . date('Y-m-d H:i:s') . PHP_EOL;

                    $server->reload();
                    $server->send($fd, '服务器Worker重启成功');

                    break;
                default:
                    $server->send($fd, '非法请求');

                    break;
            }
        } else {
            $info         = $server->connection_info($fd, $from_id);
            $clientIp     = $info['remote_ip'];  //客户端连接的ip

            //异步任务
            $data       = json_decode($data, true);
            $clientInfo = array('ip'=>$clientIp, 'requestTime'=>date('Y-m-d H:i:s'));
            $data       = array_merge($data, $clientInfo);

            $taskId = $server->task($data);
            $server->send($fd, "Dispath AsyncTask: id=$taskId\n");
        }
    }

    /**
     * 任务回调方法
     *
     * @param swoole_server $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask(swoole_server $server, $task_id, $from_id, $data) {
        $result = Logger::addReportLog($data);

        //return字符串，表示将此内容返回给worker进程。worker进程中会触发onFinish函数，表示投递的task已完成。
        return $result;
    }

    /**
     * 任务处理完毕回调方法
     * @param swoole_server $server
     * @param $task_id
     * @param $data
     */
    public function onFinish(swoole_server $server, $task_id, $data) {
    }

    //服务器关闭
    public function onShutdown(swoole_server $server) {
        echo '服务器关闭: ' . date('Y-m-d H:i:s') . PHP_EOL;

        //通知运维人员
        Alarm::noticeOperational();
    }

    /**
     * 内网管理
     *
     * @param $cmd
     * @throws Exception
     */
    private function manage($cmd) {
        $client = new swoole_client(SWOOLE_SOCK_UDP);
        $ret = $client->connect('127.0.0.1', 9604, 0.5);
        if(!$ret) {
            throw new Exception($client->errCode);
        }

        $client->send($cmd);
        $ret =  $client->recv();
        echo $ret . PHP_EOL;
    }
}

global $argv;
$startFile = $argv[0];

if(!isset($argv[1])) {
    exit("Usage: php {$startFile} {start|stop|reload}\n");
}

new Statistics($argv[1]);