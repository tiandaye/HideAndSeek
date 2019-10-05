<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-03
 * Time: 08:05
 */

namespace App\Model;

class Player
{
    const UP = 'up';
    const DOWN = 'down';
    const LEFT = 'left';
    const RIGHT = 'right';

    const DIRECTION = [self::UP, self::DOWN, self::LEFT, self::RIGHT];

    const PLAYER_TYPE_SEEK = 1;
    const PLAYER_TYPE_HIDE = 2;

    private $id;
    private $type = self::PLAYER_TYPE_SEEK;
    private $x;
    private $y;

    public function __construct($id, $x, $y)
    {
        $this->id = $id;
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * 设置类型
     *
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * 获得类型
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 获得玩家id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 获得x洲坐标
     *
     * @return mixed
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * 获得y轴坐标
     *
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * 向上
     */
    public function up()
    {
        $this->x--;
    }

    /**
     * 向下
     */
    public function down()
    {
        $this->x++;
    }

    /**
     * 向左
     */
    public function left()
    {
        $this->y--;
    }

    /**
     * 向右
     */
    public function right()
    {
        $this->y++;
    }
}