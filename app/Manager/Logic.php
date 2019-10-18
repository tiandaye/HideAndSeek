<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-05
 * Time: 16:22
 */

namespace App\Manager;

/**
 * 逻辑
 *
 * Class Logic
 * @package App\Manager
 */
class Logic
{
    /**
     * 【匹配玩家】新增matchPlayer()方法，将Server传递过来的player_id放入DataCenter的匹配队列中。
     *
     * @param $playerId
     */
    public function matchPlayer($playerId)
    {
        // 将用户放入队列中
        DataCenter::pushPlayerToWaitList($playerId);

        // 发起一个Task尝试匹配
        //发起一个Task尝试匹配
        DataCenter::$server->task(['code' => TaskManager::TASK_CODE_FIND_PLAYER]);
        // swoole_server->task(['code'=>'xxx']);
    }

    /**
     * 创建房间
     *
     * @param $redPlayer
     * @param $bluePlayer
     */
    public function createRoom($redPlayer, $bluePlayer)
    {
        $roomId = uniqid('room_');
        // fd绑定worker
        $this->bindRoomWorker($redPlayer, $roomId);
        $this->bindRoomWorker($bluePlayer, $roomId);
    }

    /**
     * 绑定worker
     *
     * @param $playerId
     * @param $roomId
     */
    private function bindRoomWorker($playerId, $roomId)
    {
        $playerFd = DataCenter::getPlayerFd($playerId);
        DataCenter::$server->bind($playerFd, crc32($roomId));
        Sender::sendMessage($playerId, Sender::MSG_ROOM_ID, ['room_id' => $roomId]);
    }
}