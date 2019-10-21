<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-05
 * Time: 16:22
 */

namespace App\Manager;

use App\Lib\Redis;

/**
 * 数据中心
 *
 * Class DataCenter
 * @package App\Manager
 */
class DataCenter
{
    // 游戏的数据主要有两个存储方式：
    // 内存：用于管理每局的游戏对战数据。
    // Redis：用于实现匹配队列、保存玩家信息数据。

    // redis前缀
    const PREFIX_KEY = 'hide_game';

    // 我们首先在DataCenter类中新增一个静态变量$global，所有的对战房间数据都将存储在这个变量中。
    // 我们的匹配队列是存放在Redis中的，无论哪个worker都可以读取，但游戏数据是存放在内存中的，在启动Swoole Worker时设置了'worker_num' => 4，有多个worker进程，这会产生什么效果呢？就是进程内存隔离。
    public static $global;

    // ws
    public static $server;


    /**
     * 获取redis实例
     *
     * @return mixed
     */
    public static function redis()
    {
        return Redis::getInstance();
    }

    /**
     * 初始化的时候清空数据
     */
    public static function initDataCenter()
    {
        // 清空匹配队列
        $key = self::PREFIX_KEY . ':player_wait_list';
        self::redis()->del($key);
        // 清空玩家ID
        $key = self::PREFIX_KEY . ':player_id*';
        $values = self::redis()->keys($key);
        foreach ($values as $value) {
            self::redis()->del($value);
        }
        // 清空玩家FD
        $key = self::PREFIX_KEY . ':player_fd*';
        $values = self::redis()->keys($key);
        foreach ($values as $value) {
            self::redis()->del($value);
        }

        // 清空玩家房间ID
        $key = self::PREFIX_KEY . ':player_room_id*';
        $values = self::redis()->keys($key);
        foreach ($values as $value) {
            self::redis()->del($value);
        }
    }

    /**
     * 获得队列长度
     *
     * @return mixed
     */
    public static function getPlayerWaitListLen()
    {
        $key = self::PREFIX_KEY . ":player_wait_list";
        return self::redis()->lLen($key);
    }

    /**
     * 插入队列
     *
     * @param $playerId
     */
    public static function pushPlayerToWaitList($playerId)
    {
        $key = self::PREFIX_KEY . ":player_wait_list";
        self::redis()->lPush($key, $playerId);
    }

    /**
     * 弹出队列
     *
     * @return mixed
     */
    public static function popPlayerFromWaitList()
    {
        $key = self::PREFIX_KEY . ":player_wait_list";
        return self::redis()->rPop($key);
    }

    /**
     * 通过 player_id 获得 fd
     *
     * @param $playerId
     * @return mixed
     */
    public static function getPlayerFd($playerId)
    {
        $key = self::PREFIX_KEY . ":player_fd:" . $playerId;
        return self::redis()->get($key);
    }

    /**
     * 设置 player_id 和 fd 的映射
     *
     * @param $playerId
     * @param $playerFd
     */
    public static function setPlayerFd($playerId, $playerFd)
    {
        $key = self::PREFIX_KEY . ":player_fd:" . $playerId;
        self::redis()->set($key, $playerFd);
    }

    /**
     * 通过 player_id 删除和 fd 的映射
     *
     * @param $playerId
     */
    public static function delPlayerFd($playerId)
    {
        $key = self::PREFIX_KEY . ":player_fd:" . $playerId;
        self::redis()->del($key);
    }

    /**
     *
     * 通过 fd 获得 player_id
     *
     * @param $playerFd
     * @return mixed
     */
    public static function getPlayerId($playerFd)
    {
        $key = self::PREFIX_KEY . ":player_id:" . $playerFd;
        return self::redis()->get($key);
    }

    /**
     * 设置 fd 和 player_id 的映射
     *
     * @param $playerFd
     * @param $playerId
     */
    public static function setPlayerId($playerFd, $playerId)
    {
        $key = self::PREFIX_KEY . ":player_id:" . $playerFd;
        self::redis()->set($key, $playerId);
    }

    /**
     * 通过 fd 删除和 player_id 的映射
     *
     * @param $playerFd
     */
    public static function delPlayerId($playerFd)
    {
        $key = self::PREFIX_KEY . ":player_id:" . $playerFd;
        self::redis()->del($key);
    }

    /**
     * 房间id和玩家id绑定
     *
     * @param $playerId
     * @param $roomId
     */
    public static function setPlayerRoomId($playerId, $roomId)
    {
        $key = self::PREFIX_KEY . ':player_room_id:' . $playerId;
        self::redis()->set($key, $roomId);
    }

    /**
     * 通过玩家id获得房间id
     *
     * @param $playerId
     * @return mixed
     */
    public static function getPlayerRoomId($playerId)
    {
        $key = self::PREFIX_KEY . ':player_room_id:' . $playerId;
        return self::redis()->get($key);
    }

    /**
     * 删除房间id和玩家id的映射
     *
     * @param $playerId
     */
    public static function delPlayerRoomId($playerId)
    {
        $key = self::PREFIX_KEY . ':player_room_id:' . $playerId;
        self::redis()->del($key);
    }

    /**
     * 设置用户信息, player_id 和 fd 映射
     *
     * @param $playerId
     * @param $playerFd
     */
    public static function setPlayerInfo($playerId, $playerFd)
    {
        self::setPlayerId($playerFd, $playerId);
        self::setPlayerFd($playerId, $playerFd);
    }

    /**
     * 删除 player_id 和 fd 映射
     *
     * @param $playerFd
     */
    public static function delPlayerInfo($playerFd)
    {
        $playerId = self::getPlayerId($playerFd);
        self::delPlayerFd($playerId);
        self::delPlayerId($playerFd);
    }

    /**
     * 打日志
     *
     * @param $info
     * @param array $context
     * @param string $level
     */
    public
    static function log($info, $context = [], $level = 'INFO')
    {
        if ($context) {
            echo sprintf("[%s][%s]: %s %s\n", date('Y-m-d H:i:s'), $level, $info,
                json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            echo sprintf("[%s][%s]: %s\n", date('Y-m-d H:i:s'), $level, $info);
        }
    }
}