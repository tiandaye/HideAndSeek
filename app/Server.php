<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-05
 * Time: 16:16
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Manager\DataCenter;
use App\Manager\Logic;
use App\Manager\TaskManager;

// onStart：启动后在 主进程（master）的主线程回调此函数。如果想要修改服务器后台进程名，就需要在这个回调函数中进行处理。
// onWorkerStart：此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。当设置了n个worker的时候，这个函数将会被回调n次。在默认的dispatch_mode模式下，每一个新连接都会随机分配一个worker进程，并且在此连接断开前每一次的message将会发送到同一个worker进程。如果我们想发送消息到客户端，就可以使用此回调函数的第一个参数$server对象。
// onOpen：当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
// onMessage：当服务器收到来自客户端的数据帧时会回调此函数。第一个参数$server与onWorkerStart()方法中的参数$server是同一个对象。第二个参数$request实际是一个swoole_websocket_frame对象，里面包含了4个属性，其中比较重要的是fd和data。fd表示当前发送消息的客户端socket id，想要给客户端发送消息就必须要通过这个fd。data则保存了客户端发来的数据。
// onClose：客户端连接关闭后，在worker进程中回调此函数，参数中的$fd就是客户端的连接fd。
/**
 * Class Server
 *
 * cd app
 * php Server.php
 */
class Server
{
    // 主机
    const HOST = '0.0.0.0';
    // 端口
    const PORT = 8811;
    // 自定义配置
    const CONFIG = [
        // worker数
        'worker_num'            => 4,
        // 开启task功能
        'task_worker_num'       => 8,
        // 将连接绑定一个用户定义的UID，可以设置dispatch_mode=5设置以此值进行hash固定分配。可以保证某一个UID的连接全部会分配到同一个Worker进程。
        'dispatch_mode'         => 5,
        // 添加Swoole Static Handler配置
        'enable_static_handler' => true,
        'document_root'         =>
            '/Users/tianwangchong/Documents/projects/my_php_projects/swoole_projects/HideAndSeek/frontend/dist/',
    ];
    // 添加Swoole Static Handler配置, 前端端口
    const FRONT_PORT = 8812;

    // 客户端传递过来的code码, 匹配玩家
    const CLIENT_CODE_MATCH_PLAYER = 600;

    // 客户端传递过来的code码, 开始房间
    const CLIENT_CODE_START_ROOM = 601;

    // 玩家移动
    const CLIENT_CODE_PLAYER_MOVE = 602;

    // 邀请对手
    const CLIENT_CODE_MAKE_CHALLENGE = 603;

    // 邀请对手, 接受挑战
    const CLIENT_CODE_ACCEPT_CHALLENGE = 604;

    // 邀请对手, 拒绝挑战
    const CLIENT_CODE_REFUSE_CHALLENGE = 605;

    // ws
    private $ws;

    // 逻辑
    private $logic;

    public function __construct()
    {
        // 在Server类初始化的时候，新建Logic对象并保存在私有变量$logic，用于调用Logic类中的方法。
        $this->logic = new Logic();

        $this->ws = new \Swoole\WebSocket\Server(self::HOST, self::PORT);
        $this->ws->set(self::CONFIG);
        // 添加Swoole Static Handler配置
        $this->ws->listen(self::HOST, self::FRONT_PORT, SWOOLE_SOCK_TCP);
        $this->ws->on('start', [$this, 'onStart']);
        $this->ws->on('workerStart', [$this, 'onWorkerStart']);
        $this->ws->on('open', [$this, 'onOpen']);
        $this->ws->on('message', [$this, 'onMessage']);
        $this->ws->on('close', [$this, 'onClose']);
        $this->ws->on('task', [$this, 'onTask']);
        $this->ws->on('finish', [$this, 'onFinish']);
        $this->ws->on('request', [$this, 'onRequest']);

        $this->ws->start();
    }

    /**
     * 主进程启动-manager进程(监视和管理进程组)
     *
     * @param $server
     */
    public function onStart($server)
    {
        swoole_set_process_name('hide-and-seek');
        // 打印日志
        echo sprintf("master start (listening on %s:%d)\n",
            self::HOST, self::PORT);

        // 初始化时清空数据
        DataCenter::initDataCenter();
    }

    /**
     * worker和task进程启动
     *
     * @param $server
     * @param $workerId
     */
    public function onWorkerStart($server, $workerId)
    {
        // 打印日志
        // 可以通过$server->taskworker属性来判断当前是Worker进程还是Task进程
        if ($server->taskworker) {
            echo "server: onTaskStart,worker_id:{$server->worker_id}\n";
        } else {
            echo "server: onWorkStart,worker_id:{$server->worker_id}\n";
        }

        // $server->manager_pid; // 管理进程的PID，通过向管理进程发送SIGUSR1信号可实现柔性重启
        // $server->master_pid; // 主进程的PID，通过向主进程发送SIGTERM信号可安全关闭服务器
        // $server->connections; // 当前服务器的客户端连接，可使用foreach遍历所有连接

        // 为什么不在onStart的时候获取？这是因为onStart回调的是Master进程，而onWorkerStart回调的是Worker进程，只有Worker进程才可以发起Task任务。
        DataCenter::$server = $server;
    }

    /**
     * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数
     * 小提示：默认情况下，一个客户端连接在onOpen时就会绑定一个worker进程，后续所有message交互都会发送到同一个worker进程。
     *
     * @param \Swoole\WebSocket\Server $server
     * @param $request
     */
    public function onOpen(Swoole\WebSocket\Server $server, $request)
    {
        // 打印日志
        // echo "server: handshake success with fd{$request->fd}\n";
        DataCenter::log(sprintf('client open fd：%d', $request->fd));
        // $server->push($request->fd, "hello");

        // 将player_id和fd绑定
        // 在onOpen事件中，传递用户的player_id和fd到DataCenter的setPlayerInfo()方法中进行保存。
        $playerId = $request->get['player_id'];
        // DataCenter::setPlayerInfo($playerId, $request->fd);

        // 如果在线则拒绝
        if (empty(DataCenter::getOnlinePlayer($playerId))) {
            DataCenter::setPlayerInfo($playerId, $request->fd);
        } else {
            $server->disconnect($request->fd, 4000, '该player_id已在线');
        }
    }

    /**
     * 客户端关闭
     *
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        // 打印日志
        // echo "client {$fd} closed\n";
        DataCenter::log(sprintf('client close fd：%d', $fd));

        // 在onClose事件中，调用delPlayerInfo()方法根据$fd清除玩家信息
        DataCenter::delPlayerInfo($fd);

        // 玩家退出, 通知对手
        $this->logic->closeRoom(DataCenter::getPlayerId($fd));
    }

    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数
     *
     * @param \Swoole\WebSocket\Server $server
     * @param $frame
     */
    public function onMessage(Swoole\WebSocket\Server $server, $frame)
    {
        // 打印日志
        // $data = json_encode($frame->data);
        // echo "receive from {$frame->fd}:{$data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        DataCenter::log(sprintf('client open fd：%d，message：%s', $frame->fd, $frame->data));
        // 往前端发送消息
        // $server->push($frame->fd, $data);

        // 接受前端的数据。在onMessage事件中，根据当前连接的fd获取player_id，当前端发送的消息中的code为600时，调用Logic中的matchPlayer()方法
        $data = json_decode($frame->data, true);
        $playerId = DataCenter::getPlayerId($frame->fd);
        switch ($data['code']) {
            // 匹配玩家
            case self::CLIENT_CODE_MATCH_PLAYER:
                $this->logic->matchPlayer($playerId);
                break;

            // 开始游戏
            case self::CLIENT_CODE_START_ROOM:
                $this->logic->startRoom($data['room_id'], $playerId);
                break;

            // 玩家移动
            case self::CLIENT_CODE_PLAYER_MOVE:
                $this->logic->playerMove($playerId, $data['direction']);
                break;

            // 邀请对手
            case self::CLIENT_CODE_MAKE_CHALLENGE:
                $this->logic->makeChallenge($data['opponent_id'], $playerId);
                break;

            // 邀请对手, 接受挑战
            case self::CLIENT_CODE_ACCEPT_CHALLENGE:
                $this->logic->acceptChallenge($data['challenger_id'], $playerId);
                break;

            // 邀请对手, 拒绝挑战
            case self::CLIENT_CODE_REFUSE_CHALLENGE:
                $this->logic->refuseChallenge($data['challenger_id']);
                break;
        }
    }

    /**
     * 接收到task
     *
     * @param \Swoole\WebSocket\Server $server
     * @param $taskId
     * @param $srcWorkerId
     * @param $data
     * @return array
     */
    public function onTask(Swoole\WebSocket\Server $server, $taskId, $srcWorkerId, $data)
    {
        // 打印日志
        // echo "Tasker进程接收到数据";
        // echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$taskId, data_len=" . strlen($data) . "." . PHP_EOL;
        // $server->finish($data);
        DataCenter::log("onTask", $data);

        $result = [];
        // 执行某些逻辑
        switch ($data['code']) {
            // 执行task方法
            case TaskManager::TASK_CODE_FIND_PLAYER:
                $ret = TaskManager::findPlayer();
                if (!empty($ret)) {
                    $result['data'] = $ret;

                    // 或者直接返回result
                    // $server->finish([
                    //     'code' => $data['code'],
                    //     'data' => $result
                    // ]);
                }
                break;

            case 'yyy':
                // task->yyy();
                break;
        }

        if (!empty($result)) {
            $result['code'] = $data['code'];
            return $result;
        }
    }

    /**
     * task执行完成
     *
     * @param \Swoole\WebSocket\Server $server
     * @param $taskId
     * @param $data
     */
    public function onFinish(Swoole\WebSocket\Server $server, $taskId, $data)
    {
        // 打印日志
        // echo "Task#$taskId finished, data_len=" . strlen($data) . PHP_EOL;
        DataCenter::log("onFinish", $data);

        switch ($data['code']) {
            case TaskManager::TASK_CODE_FIND_PLAYER:
                // 人数满足条件创建房间
                $this->logic->createRoom($data['data']['red_player'],
                    $data['data']['blue_player']);
                break;
        }
    }

    /**
     * http请求
     *
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response)
    {
        // $response->end('HelloWorld');
        DataCenter::log("onRequest");
        $action = $request->get['a'];
        if ($action == 'get_online_player') {
            $data = [
                'online_player' => DataCenter::lenOnlinePlayer()
            ];
            $response->end(json_encode($data));
        } elseif ($action == 'get_player_rank') {
            $data = [
                'players_rank' => DataCenter::getPlayersRank()
            ];
            $response->end(json_encode($data));
        }
    }
}

new Server();