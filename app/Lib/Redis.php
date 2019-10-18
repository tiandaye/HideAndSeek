<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-17
 * Time: 18:19
 */

namespace App\Lib;

/**
 * Redis类
 *
 * Class Redis
 * @package App\Lib
 */
class Redis
{
    protected static $instance;
    protected static $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
    ];

    /**
     * 获取redis实例
     *
     * @return \Redis|\RedisCluster
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            $instance = new \Redis();
            $instance->connect(
                self::$config['host'],
                self::$config['port']
            );
            self::$instance = $instance;
        }
        return self::$instance;
    }
}