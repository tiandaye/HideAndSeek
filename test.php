<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-03
 * Time: 08:34
 */

ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
// // 禁用错误报告
// error_reporting(0);
// // 报告运行时错误
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
// // 报告所有错误
// error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use App\Manager\Game;
use App\Model\Player;

$redId = "red_player";
$blueId = "blue_player";

// 创建游戏控制器
$game = new Game();
// 添加玩家
$game->createPlayer($redId, 6, 1);
// 添加玩家
$game->createPlayer($blueId, 6, 10);
// // 移动坐标
// $game->playerMove($redId, 'up');
// $game->playerMove($redId, 'up');
// $game->playerMove($redId, 'up');
// // 打印地图
// $game->printGameMap();

for ($i = 0; $i <= 300; $i++) {
    $direct = mt_rand(0, 3);
    $game->playerMove($redId, Player::DIRECTION[$direct]);
    if ($game->isGameOver()) {
        $game->printGameMap();
        echo "game_over" . PHP_EOL;
        return;
    }
    $direct = mt_rand(0, 3);
    $game->playerMove($blueId, Player::DIRECTION[$direct]);
    if ($game->isGameOver()) {
        $game->printGameMap();
        echo "game_over" . PHP_EOL;
        return;
    }
    // 打印移动后战局
    echo PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
    $game->printGameMap();
    usleep(200000);
}