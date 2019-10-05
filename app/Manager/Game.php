<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-03
 * Time: 08:03
 */

namespace App\Manager;

use App\Model\Map;
use App\Model\Player;

class Game
{
    private $gameMap = [];
    private $players = [];

    public function __construct()
    {
        $this->gameMap = new Map(12, 12);
    }

    /**
     * 判断游戏是否结束
     *
     * @return bool
     */
    public function isGameOver()
    {
        $result = false;
        $x = -1;
        $y = -1;
        $players = array_values($this->players);
        /* @var Player $player */
        foreach ($players as $key => $player) {
            if ($key == 0) {
                $x = $player->getX();
                $y = $player->getY();
            } elseif ($x == $player->getX() && $y == $player->getY()) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * 创建玩家
     *
     * @param $playerId
     * @param $x
     * @param $y
     */
    public function createPlayer($playerId, $x, $y)
    {
        $player = new Player($playerId, $x, $y);
        if (!empty($this->players)) {
            $player->setType(Player::PLAYER_TYPE_HIDE);
        }
        $this->players[$playerId] = $player;
    }

    /**
     * 玩家移动
     *
     * @param $playerId
     * @param $direction
     */
    public function playerMove($playerId, $direction)
    {
        $player = $this->players[$playerId];
        if ($this->canMoveToDirection($player, $direction)) {
            $player->{$direction}();
        }
        // $this->players[$playerId]->{$direction}();
    }

    /**
     * 判断能够移动
     *
     * @param $player
     * @param $direction
     * @return bool
     */
    public function canMoveToDirection($player, $direction)
    {
        $x = $player->getX();
        $y = $player->getY();

        $moveCoor = $this->getMoveCoor($x, $y, $direction);

        $mapData = $this->gameMap->getMapData();

        return !empty($mapData[$moveCoor[0]][$moveCoor[1]]);
    }

    /**
     * 准备移动后的坐标
     *
     * @param $x
     * @param $y
     * @param $direction
     * @return array
     */
    private function getMoveCoor($x, $y, $direction)
    {
        switch ($direction) {
            case Player::UP:
                return [--$x, $y];
            case Player::DOWN:
                return [++$x, $y];
            case Player::LEFT:
                return [$x, --$y];
            case Player::RIGHT:
                return [$x, ++$y];
        }
        return [$x, $y];
    }

    /**
     * 打印地图
     */
    public function printGameMap()
    {
        $map = $this->gameMap->getMapData();

        $font = [2 => '追', 3 => '躲'];
        /* @var Player $player */
        foreach ($this->players as $player) {
            $map[$player->getX()][$player->getY()] = $player->getType() + 1;
        }

        foreach ($map as $line) {
            foreach ($line as $item) {
                if (empty($item)) {
                    echo "墙，";
                } elseif ($item == 1) {
                    echo "    ";
                } else {
                    echo $font[$item] . '，';
                }
            }
            echo PHP_EOL;
        }
    }
}