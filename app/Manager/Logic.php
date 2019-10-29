<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-05
 * Time: 16:22
 */

namespace App\Manager;

use App\Model\Player;

/**
 * 逻辑
 *
 * Class Logic
 * @package App\Manager
 */
class Logic
{
    // 显示玩家附近几步的数据
    const PLAYER_DISPLAY_LEN = 2;

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
     * 开始游戏
     *
     * @param $roomId
     * @param $playerId
     */
    public function startRoom($roomId, $playerId)
    {
        if (!isset(DataCenter::$global['rooms'][$roomId])) {
            DataCenter::$global['rooms'][$roomId] = [
                'id'      => $roomId,
                'manager' => new Game()
            ];
        }

        /**
         * @var Game $gameManager
         */
        $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
        if (empty(count($gameManager->getPlayers()))) {
            // 第一个玩家
            $gameManager->createPlayer($playerId, 6, 1);
            Sender::sendMessage($playerId, Sender::MSG_WAIT_PLAYER);
        } else {
            // 第二个玩家
            $gameManager->createPlayer($playerId, 6, 10);
            Sender::sendMessage($playerId, Sender::MSG_ROOM_START);
            $this->sendGameInfo($roomId);
        }

        // if (isset(DataCenter::$global[$roomId])) {
        //     $game = DataCenter::$global[$roomId];
        //     // 添加玩家
        //     $game->createPlayer($playerId, mt_rand(1, 9), mt_rand(1, 9));
        // } else {
        //     // 创建游戏控制器
        //     $game = new Game();
        //     // 添加玩家
        //     $game->createPlayer($playerId, mt_rand(1, 9), mt_rand(1, 9));
        //     DataCenter::$global[$roomId] = $game;
        // }
    }

    /**
     * 玩家移动
     *
     * @param $playerId
     * @param $direction
     */
    public function playerMove($playerId, $direction)
    {
        // 方向是否合法
        if (!in_array($direction, Player::DIRECTION)) {
            echo $direction;
            return;
        }

        // 通过玩家id获得房间id
        $roomId = DataCenter::getPlayerRoomId($playerId);

        // 是否能够找到房间
        if (isset(DataCenter::$global['rooms'][$roomId])) {
            /**
             * @var Game $gameManager
             */
            $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
            $gameManager->playerMove($playerId, $direction);
            $this->sendGameInfo($roomId);
            // 判断游戏是否结束
            $this->checkGameOver($roomId);
        }
    }

    /**
     * 玩家退出, 通知对手. 关闭房间
     *
     * @param $closerId
     */
    public function closeRoom($closerId)
    {
        $roomId = DataCenter::getPlayerRoomId($closerId);
        if (!empty($roomId)) {
            /**
             * @var Game $gameManager
             * @var Player $player
             */
            $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
            $players = $gameManager->getPlayers();
            foreach ($players as $player) {
                if ($player->getId() != $closerId) {
                    Sender::sendMessage($player->getId(), Sender::MSG_OTHER_CLOSE);
                }
                DataCenter::delPlayerRoomId($player->getId());
            }
            unset(DataCenter::$global['rooms'][$roomId]);
        }
    }

    /**
     * 邀请对手
     *
     * @param $opponentId
     * @param $playerId
     */
    public function makeChallenge($opponentId, $playerId)
    {
        if (empty(DataCenter::getOnlinePlayer($opponentId))) {
            Sender::sendMessage($playerId, Sender::MSG_OPPONENT_OFFLINE);
        } else {
            $data = [
                'challenger_id' => $playerId
            ];
            Sender::sendMessage($opponentId, Sender::MSG_MAKE_CHALLENGE, $data);
        }
    }

    /**
     * 邀请对手, 接受挑战
     *
     * @param $challengerId
     * @param $playerId
     */
    public function acceptChallenge($challengerId, $playerId)
    {
        $this->createRoom($challengerId, $playerId);
    }

    /**
     * 邀请对手, 拒绝接受
     *
     * @param $challengerId
     */
    public function refuseChallenge($challengerId)
    {
        Sender::sendMessage($challengerId, Sender::MSG_REFUSE_CHALLENGE);
    }

    /**
     * 发送游戏信息
     *
     * @param $roomId
     */
    private function sendGameInfo($roomId)
    {
        /**
         * @var Game $gameManager
         * @var Player $player
         */
        $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
        $players = $gameManager->getPlayers();
        $mapData = $gameManager->getMapData();
        // foreach ($players as $player) {
        //     $mapData[$player->getX()][$player->getY()] = $player->getId();
        // }
        // 必须倒序输出，因为游戏设定数组第一个是寻找者，第二个是躲藏者，叠加时赢的是寻找者。
        foreach (array_reverse($players) as $player) {
            $mapData[$player->getX()][$player->getY()] = $player->getId();
        }

        foreach ($players as $player) {
            $data = [
                'players'  => $players,
                'map_data' => $this->getNearMap($mapData, $player->getX(), $player->getY())
            ];
            Sender::sendMessage($player->getId(), Sender::MSG_GAME_INFO, $data);
        }
    }

    /**
     * 根据地图数据以及玩家坐标，仅返回玩家坐标附近范围为2的地图数据。
     *
     * @param $mapData
     * @param $x
     * @param $y
     * @return array
     */
    private function getNearMap($mapData, $x, $y)
    {
        $result = [];
        for ($i = -1 * self::PLAYER_DISPLAY_LEN; $i <= self::PLAYER_DISPLAY_LEN; $i++) {
            $tmp = [];
            for ($j = -1 * self::PLAYER_DISPLAY_LEN; $j <= self::PLAYER_DISPLAY_LEN; $j++) {
                $tmp[] = $mapData[$x + $i][$y + $j] ?? 0;
            }
            $result[] = $tmp;
        }
        return $result;
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
        // 将连接绑定一个用户定义的UID，可以设置dispatch_mode=5设置以此值进行hash固定分配。可以保证某一个UID的连接全部会分配到同一个Worker进程。
        // function Server->bind(int $fd, int $uid);
        // $fd：连接的ID
        // $uid：要绑定的UID，必须为非0的数字
        // 未绑定UID时默认使用fd取模进行分配
        // 同一个连接只能被bind一次，如果已经绑定了UID，再次调用bind会返回false
        // 可以使用$serv->getClientInfo($fd) 查看连接所绑定UID的值
        // 仅在设置dispatch_mode=5时有效
        // 在默认的dispatch_mode=2设置下，Server会按照socket fd来分配连接数据到不同的Worker进程。因为fd是不稳定的，一个客户端断开后重新连接，fd会发生改变。这样这个客户端的数据就会被分配到别的Worker。使用bind之后就可以按照用户定义的UID进行分配。即使断线重连，相同UID的TCP连接数据会被分配相同的Worker进程。
        DataCenter::$server->bind($playerFd, crc32($roomId));

        // 房间id和玩家id映射绑定
        DataCenter::setPlayerRoomId($playerId, $roomId);

        Sender::sendMessage($playerId, Sender::MSG_ROOM_ID, ['room_id' => $roomId]);
    }

    /**
     * 游戏是否结束
     *
     * @param $roomId
     */
    private function checkGameOver($roomId)
    {
        /**
         * @var Game $gameManager
         * @var Player $player
         */
        $gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
        if ($gameManager->isGameOver()) {
            $players = $gameManager->getPlayers();
            $winner = current($players)->getId();
            foreach ($players as $player) {
                Sender::sendMessage($player->getId(), Sender::MSG_GAME_OVER, ['winner' => $winner]);
                DataCenter::delPlayerRoomId($player->getId());
            }
            unset(DataCenter::$global['rooms'][$roomId]);

            // 添加胜利的局数
            DataCenter::addPlayerWinTimes($winner);
        }
    }
}