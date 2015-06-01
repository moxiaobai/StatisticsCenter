<?php

namespace Service;

/**
 * 报警系统
 * 通知方式：短信通知、RTX通知、邮件通知
 *
 * @author: moxiaobai
 * @since : 2015/5/11 15:15
 */


class Alarm {

    /**
     * 报警：通知程序负责人处理
     *
     * @param $uid     程序员用户ID
     * @return mixed
     */
    public static function noticeProgrammer($uid) {
        return true;
    }


    /**
     * 报警：通知运维人员处理
     *
     * @return mixed
     */
    public static function noticeOperational() {
        return true;
    }

    /**
     * 获取程序员用户信息
     *
     * @param $uid
     * @return mixed
     * @throws Exception
     */
    private function getProgrammerInfo($uid) {
        return true;
    }
}