<?php
/**
 * Created by PhpStorm.
 * User: tianwangchong
 * Date: 2019-10-05
 * Time: 16:16
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Manager\DataCenter;

/**
 * Class Server
 *
 * cd app
 * php Server.php
 */
class Server
{
    const HOST = '0.0.0.0';
    const PORT = 8811;
    const CONFIG = [
        'worker_num'            => 4,
        // 添加Swoole Static Handler配置
        'enable_static_handler' => true,
        'document_root'         =>
            '/Users/tianwangchong/Documents/projects/my_php_projects/swoole_projects/HideAndSeek/frontend/dist/',
    ];
    // 添加Swoole Static Handler配置
    const FRONT_PORT = 8812;

    private $ws;

    public function __construct()
    {
        $this->ws = new \Swoole\WebSocket\Server(self::HOST, self::PORT);
        $this->ws->set(self::CONFIG);
        // 添加Swoole Static Handler配置
        $this->ws->listen(self::HOST, self::FRONT_PORT, SWOOLE_SOCK_TCP);
        $this->ws->on('start', [$this, 'onStart']);
        $this->ws->on('workerStart', [$this, 'onWorkerStart']);
        $this->ws->on('open', [$this, 'onOpen']);
        $this->ws->on('message', [$this, 'onMessage']);
        $this->ws->on('close', [$this, 'onClose']);
        $this->ws->start();
    }

    public function onStart($server)
    {
        swoole_set_process_name('hide-and-seek');
        echo sprintf("master start (listening on %s:%d)\n",
            self::HOST, self::PORT);
    }

    public function onWorkerStart($server, $workerId)
    {
        echo "server: onWorkStart,worker_id:{$server->worker_id}\n";
    }

    /**
     * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数
     *
     * @param \Swoole\WebSocket\Server $server
     * @param $request
     */
    public function onOpen(Swoole\WebSocket\Server $server, $request)
    {
        echo "server: handshake success with fd{$request->fd}\n";
        DataCenter::log(sprintf('client open fd：%d', $request->fd));
        $server->push($request->fd, "hello");
    }

    /**
     * 客户端关闭
     *
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        echo "client {$fd} closed\n";
        DataCenter::log(sprintf('client close fd：%d', $fd));
    }

    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数
     *
     * @param \Swoole\WebSocket\Server $server
     * @param $frame
     */
    public function onMessage(Swoole\WebSocket\Server $server, $frame)
    {
        $data = json_encode($frame->data);
        echo "receive from {$frame->fd}:{$data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        DataCenter::log(sprintf('client open fd：%d，message：%s', $frame->fd, $frame->data));
        $server->push($frame->fd, $data);
    }
}

new Server();