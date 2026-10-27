<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hughcube\PhpInfo\PhpInfo;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9501);

$server->on('start', function (Server $server) {
    echo "Swoole HTTP server is listening on http://0.0.0.0:9501 (Host mapping: http://127.0.0.1:9502)\n";
});

$server->on('request', function (Request $request, Response $response) {
    $uri = $request->server['request_uri'] ?? '/';

    // 1. 原生 raw 文本对比接口
    if ($uri === '/raw') {
        ob_start();
        phpinfo();
        $raw = ob_get_clean();

        $response->header('Content-Type', 'text/plain; charset=utf-8');
        $response->end($raw);
        return;
    }

    // 2. 默认输出 1:1 复刻的 HTML 页面
    $html = PhpInfo::html();

    $response->header('Content-Type', 'text/html; charset=utf-8');
    $response->end($html);
});

$server->start();
