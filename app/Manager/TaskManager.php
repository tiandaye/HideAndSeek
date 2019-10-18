<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-18
 * Time: 09:40
 */

namespace App\Manager;

/**
 * Task管理类：可以使用一个类来管理Task的code和Task需要执行的逻辑方法
 *
 * Class TaskManager
 * @package App\Manager
 */
class TaskManager
{
    // 用于发起寻找玩家task任务
    const TASK_CODE_FIND_PLAYER = 1;

    /**
     * 当匹配队列长度大于等于2时，弹出队列前两个玩家的player_id并返回。
     *
     * @return array|bool
     */
    public static function findPlayer()
    {
        $playerListLen = DataCenter::getPlayerWaitListLen();
        if ($playerListLen >= 2) {
            $redPlayer = DataCenter::popPlayerFromWaitList();
            $bluePlayer = DataCenter::popPlayerFromWaitList();
            return [
                'red_player'  => $redPlayer,
                'blue_player' => $bluePlayer
            ];
        }
        return false;
    }
}