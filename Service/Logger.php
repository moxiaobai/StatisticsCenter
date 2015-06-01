<?php

/**
 *  日志系统类
 *
 * @author: moxiaobai
 * @since : 2015/5/7 12:06
 */

namespace Service;

use Library\Db\MongoDb;

class Logger {



    /**
     * 统计日志
     *
     * @param $data
     */
    public static function addReportLog($data) {
        $apiName  = "{$data['module']}.{$data['interface']}";
        $costTime = $data['costTime'];
        $result   = $data['success'];
        $msg      = $data['msg'];


        $log = array(
            'apiName'     => $apiName,
            'requestTime' => $data['requestTime'],
            'ip'          => $data['ip'],
            'costTime'    => $costTime,
            'result'      => $result,
            'msg'         => $msg
        );
        self::addApiLog($log);

        $isExistList = self::isExistList($apiName);
        if($isExistList) {
            self::updateApiList($apiName, $costTime, $result);
        } else {
            self::addApiList($apiName, $costTime);
        }
    }

    private function isExistList($apiName) {
        $instance = MongoDb::instance('statistics');
        $instance->setCollection('apiList');

        $where = array('apiName'=>$apiName);
        return $instance->findOne($where);
    }

    private function addApiLog($data) {
        $instance = MongoDb::instance('statistics');
        $instance->setCollection('apiLog');

        return $instance->insert($data);
    }

    private function addApiList($apiName, $costTime) {
        $instance = MongoDb::instance('statistics');
        $instance->setCollection('apiList');

        $list = array(
            'apiName'         => $apiName,
            'requestsNumber'  => 1,
            'failNumber'      => 0,
            'ResponseTime'    => $costTime
        );
        return $instance->insert($list);
    }

    private function updateApiList($apiName, $costTime, $result) {
        $instance = MongoDb::instance('statistics');
        $instance->setCollection('apiList');

        $where = array('apiName'=>$apiName);

        if($result) {
            $failNumber = 0;
        } else {
            $failNumber = 1;
        }
        $data  = array('$inc'=>array('requestsNumber'=>1, 'ResponseTime'=>$costTime, 'failNumber'=>$failNumber));
        return $instance->update($where, $data);
    }
}