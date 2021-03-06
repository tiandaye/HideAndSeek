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
    const MSG_ROOM_ID = 1001; // 房间绑定
    const MSG_WAIT_PLAYER = 1002; // 等待玩家
    const MSG_ROOM_START = 1003; // 开始游戏
    const MSG_GAME_INFO = 1004; // 游戏信息
    const MSG_GAME_OVER = 1005; // 游戏结束
    const MSG_OTHER_CLOSE = 1006; // 玩家退出, 通知对手
    const MSG_OPPONENT_OFFLINE = 1007; // 邀请对手, 没在线
    const MSG_MAKE_CHALLENGE = 1008; // 邀请对手
    const MSG_REFUSE_CHALLENGE = 1009; // 邀请对手, 拒绝接受

    // 消息
    const CODE_MSG = [
        self::MSG_ROOM_ID          => '房间ID',
        self::MSG_WAIT_PLAYER      => '等待其他玩家中……',
        self::MSG_ROOM_START       => '游戏开始啦~',
        self::MSG_GAME_INFO        => 'game info',
        self::MSG_GAME_OVER        => '游戏结束啦~',
        self::MSG_OTHER_CLOSE      => '你的敌人跑路了',
        self::MSG_OPPONENT_OFFLINE => '对手不在线',
        self::MSG_MAKE_CHALLENGE   => '发起挑战',
        self::MSG_REFUSE_CHALLENGE => '对方拒绝了你的挑战',
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