<?php

namespace Config;

/**
 * MongoDb 配置文件
 *
 * @package Config
 */
class Mongo  {

    public static $statistics = array(
        'product' => array(
            'host'      => '192.168.1.3',
            'port'      => '27017',
            'database'  => 'statistics'
        ),
        'develop' => array(
            'host'      => '192.168.1.3',
            'port'      => '27017',
            'database'  => 'statistics'
        )
    );

    public static function getConfig($name, $section='product') {
        $config = self::$$name;

        if(empty($config)) {
            throw new \Exception("配置文件不存在");
        }

        return $config[$section];
    }
}

