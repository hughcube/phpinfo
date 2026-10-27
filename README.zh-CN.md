<h1 align="center">hughcube/phpinfo</h1>

<p align="center">
    在 CLI、Swoole、Workerman、RoadRunner 等非 Web SAPI 环境下，将 <code>phpinfo()</code> 纯文本输出 <b>1:1 高保真无损复刻为各 PHP 版本官方原生 HTML 页面</b>。
</p>

<p align="center">
    <a href="https://github.com/hughcube/phpinfo/actions"><img src="https://github.com/hughcube/phpinfo/workflows/Test/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/v/hughcube/phpinfo.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/php-v/hughcube/phpinfo.svg" alt="PHP Version Support"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/dt/hughcube/phpinfo.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/l/hughcube/phpinfo.svg" alt="License"></a>
</p>

<p align="center">
    <a href="README.md">English</a> | <b>简体中文</b>
</p>

---

## 💡 为什么需要本扩展包？

在 PHP 源码内核（`ext/standard/info.c`）中，`phpinfo()` 究竟输出格式化 HTML 还是纯文本，由 SAPI 结构体内部成员 `sapi_module.phpinfo_as_text` 决定：
* **Web SAPI（FPM、Apache mod_php、内置 cli-server）**：`phpinfo_as_text = 0`（输出包含内嵌 CSS、表格结构与 Logo 的完整 HTML 页面）。
* **CLI SAPI（CLI 命令行、Swoole HTTP 服务器、Workerman、RoadRunner）**：`phpinfo_as_text = 1`（输出无样式的纯文本 `key => value` 列表）。

在 Swoole、Workerman 或 Laravel Octane 等常驻内存服务中，当需要提供 `/phpinfo` 运维排查接口时，直接调用 `phpinfo()` 只会吐出未排版的纯文本，阅读体验极差。

**`hughcube/phpinfo`** 通过在内存中捕获文本流，利用 AST 状态机精准反向解析，并按当前运行的真实 PHP 版本规范，**1:1 高保真重新组装出官方原汁原味的 HTML 页面**。

---

## 🌟 核心特性

* **⚡ 智能环境感知**：自动识别当前运行环境（如 FPM / Apache）是否原生支持 HTML 输出。
* **🔄 自适应零损耗输出**：
  * 在 FPM / Apache 环境下：直接放行原生 HTML 输出（100% 字节级一致，零额外开销）。
  * 在 Swoole / Workerman / RoadRunner / CLI 环境下：自动无缝转为 1:1 原生样式 HTML。
* **🎨 多版本高保真主题引擎**：
  * 完整收录官方 PHP 5.3 至 PHP 8.5+ 各阶段样式表。
  * 完美支持 PHP 8.2+ 官方暗黑模式自适应（`prefers-color-scheme: dark`）、表头粘滞定位（Sticky Headers）及内联 Base64 官方图标。
* **🛡️ 纯内存 AST 解析，安全无依赖**：
  * 不调用任何外部子进程（无需 `proc_open`/`exec`），在 `disable_functions` 严格受限的生产环境畅行无阻。
* **🧪 跨版本全矩阵严格测试**：
  * 在 GitHub Actions 上完整通过 `[5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5]` 全量 12 个 PHP 版本的自动化 CI 验证。

---

## 📦 安装方法

```bash
composer require hughcube/phpinfo
```

---

## 🚀 快速上手

### 1. 自适应输出（推荐）

```php
use Hughcube\PhpInfo\PhpInfo;

// 自动探测环境：Web SAPI 下直接返回原生 HTML，Swoole/CLI 下返回 1:1 复刻 HTML
$html = PhpInfo::html();
```

### 2. Swoole HTTP 服务集成

```php
use Hughcube\PhpInfo\PhpInfo;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9501);

$server->on('request', function (Request $request, Response $response) {
    if ($request->server['request_uri'] === '/phpinfo') {
        $response->header('Content-Type', 'text/html; charset=utf-8');
        $response->end(PhpInfo::html());
        return;
    }

    $response->end('OK');
});

$server->start();
```

### 3. Workerman / Webman 集成

```php
use Hughcube\PhpInfo\PhpInfo;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

$worker->onMessage = function (TcpConnection $connection, Request $request) {
    if ($request->path() === '/phpinfo') {
        $connection->send(new Response(200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ], PhpInfo::html()));
        return;
    }
};
```

### 4. 高级定制调用

```php
use Hughcube\PhpInfo\PhpInfo;

// 检查当前环境是否原生输出 HTML
if (PhpInfo::supportsNativeHtml()) {
    // 处于 FPM 或 Apache 等 Web 模式
}

// 强制走纯 PHP 解析渲染（即使在 FPM 环境下也强制重新装配）
$html = PhpInfo::forceRender();

// 强制使用特定版本的样式规范渲染（例如强制使用 PHP 8.2 暗黑模式）
$darkHtml = PhpInfo::forceRender(INFO_ALL, '8.2');

// 仅渲染片段（只生成 <table> 数据表格，不包含 <html>, <head>, <body> 外部骨架）
$tablesOnly = PhpInfo::forceRender(INFO_ALL, null, true);
```

---

## 📊 兼容性矩阵

| PHP 版本 | 适配主题 | 暗黑模式 | 表头粘滞 | 官方 Logo |
| :---: | :---: | :---: | :---: | :---: |
| **< 5.6** | PHP 5.6 主题 | ❌ | ❌ | ✅ |
| **5.6 ~ 7.0** | PHP 5.6 主题 | ❌ | ❌ | ✅ |
| **7.0 ~ 7.4** | PHP 7.0 主题 | ❌ | ❌ | ✅ |
| **7.4 ~ 8.2** | PHP 7.4 主题 | ❌ | ❌ | ✅ |
| **8.2 ~ 8.4** | PHP 8.2 主题 | ✅ | ❌ | ✅ |
| **8.4 ~ 8.5** | PHP 8.4 主题 | ✅ | ✅ | ✅ |
| **> 8.5** | PHP 8.5 主题 | ✅ | ✅ | ✅ |

---

## 🧪 单元测试

```bash
composer test
```

---

## 📄 开源许可证

本项目基于 [MIT License](LICENSE) 开源发布。
