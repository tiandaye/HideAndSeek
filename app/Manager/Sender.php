<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-18
 * Time: 10:30
 */

namespace App\Manager;

/**
 * 消息发送类
 * 没错，我们的push()方法直接就把room_id发过去了。又是这种问题：接收方无法识别该消息是何种消息。那么我们要如何处理呢？还是老套路，加code协议码。一个更好的办法是，找一个类来专门管理与发送相关的变量和方法。
 *
 * Class Sender
 * @package App\Manager
 */
class Sender
{
    // 作为发送room_id的code
    const MSG_ROOM_ID = 1001;

    const CODE_MSG = [
        self::MSG_ROOM_ID => '房间ID',
    ];

    /**
     * 发送消息
     *
     * @param $playerId 玩家id
     * @param $code 编码
     * @param array $data 数据
     */
    public static function sendMessage($playerId, $code, $data = [])
    {
        $message = [
            'code' => $code,
            'msg'  => self::CODE_MSG[$code] ?? '',
            'data' => $data
        ];
        $playerFd = DataCenter::getPlayerFd($playerId);
        if (empty($playerFd)) {
            return;
        }
        DataCenter::$server->push($playerFd, json_encode($message));
    }
}