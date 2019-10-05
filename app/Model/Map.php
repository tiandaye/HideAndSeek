<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-03
 * Time: 08:05
 */

namespace App\Model;

class Map
{
    // 高
    private $height;
    // 宽
    private $width;

    // 地图
    private $map = [
        [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
        [0, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0],
        [0, 0, 0, 0, 1, 0, 1, 1, 0, 1, 0, 0],
        [0, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0],
        [0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0],
        [0, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0],
        [0, 1, 1, 0, 0, 1, 0, 1, 1, 1, 1, 0],
        [0, 1, 1, 0, 1, 1, 1, 1, 0, 1, 0, 0],
        [0, 1, 1, 0, 0, 1, 1, 0, 1, 0, 1, 0],
        [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
        [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    ];

    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * 获得地图数据
     *
     * @return array
     */
    public function getMapData()
    {
        return $this->map;
    }
}