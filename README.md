# youvision/think-imgsrc

从 HTML 字符串、文章内容中高效提取图片地址的 ThinkPHP 8 组件。

## 特性

- 单张提取支持字符串定位模式，比常规正则更快
- 批量提取基于统一的正则解析，结果稳定
- 支持按序号提取、正序 / 倒序、区间提取
- 支持地址黑名单、结果去重、数量限制
- 支持 `src`、`data-src`、`data-original`、`data-lazy-src` 等属性及优先级
- 支持相对地址、协议相对地址（`//cdn.example.com/a.png`）补全
- 支持批量替换 HTML 中的图片地址
- 适配 ThinkPHP 8：服务自动注册、配置自动合并、门面调用、容器绑定
- 核心类不依赖容器，可脱离 ThinkPHP 独立使用

## 环境要求

- PHP >= 8.0
- ThinkPHP ^8.0

## 安装

```bash
composer require youvision/think-imgsrc
```

标准 ThinkPHP 8 项目的 `composer.json` 已包含以下脚本：

```json
"scripts": {
    "post-autoload-dump": [
        "@php think service:discover",
        "@php think vendor:publish"
    ]
}
```

安装或更新依赖后会扫描本包的 `extra.think.services` 与 `extra.think.config`，自动注册服务并按需发布配置文件。如果服务未生效，可执行：

```bash
php think service:discover
```

也可以手动在 `app/service.php` 中加入：

```php
return [
    \youvision\thinkimgsrc\ImgsrcService::class,
];
```

## 配置

无需复制配置文件，服务会自动合并默认配置。需要覆盖时，在项目 `config/imgsrc.php` 中只写要修改的配置项：

```php
<?php

return [
    'attrs' => ['data-src', 'data-original', 'src'],
    'blacklist' => ['avatar', 'emoji'],
    'base_url' => 'https://www.example.com',
    'absolute' => true,
];
```

完整配置项：

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `model` | `string` | 默认提取模式。`string` 为字符串定位模式，单张提取更快；`preg` 为正则模式 |
| `order` | `asc` | 默认顺序。`asc` 正序，`desc` 倒序 |
| `attrs` | `['src', 'data-src', 'data-original', 'data-lazy-src']` | 图片地址属性及优先级，按顺序命中第一个非空属性 |
| `blacklist` | `[]` | 地址黑名单，地址包含其中任意字符串即被排除 |
| `base_url` | 空 | 相对地址补全的基准地址，如 `https://www.example.com` |
| `absolute` | `false` | 是否自动将相对地址补全为绝对地址，需同时配置 `base_url` |
| `dedupe` | `true` | 列表结果是否去重 |
| `protocol_relative` | `true` | 是否将 `//cdn.example.com/a.png` 补全协议 |
| `protocol` | `https` | 协议相对地址补全使用的协议，可选 `http`、`https` |
| `limit` | `0` | 列表方法最多返回数量，`0` 表示不限制 |

配置合并后可通过 ThinkPHP 配置读取：

```php
config('imgsrc.attrs');
```

也可在运行时读取核心类的完整配置：

```php
\youvision\thinkimgsrc\Imgsrc::config();
```

## 基础用法

```php
use youvision\thinkimgsrc\Imgsrc;

$html = <<<HTML
<p>正文内容</p>
<img src="/uploads/a.png">
<img data-src="//cdn.example.com/b.png">
<img src="/uploads/c.png">
HTML;

// 第一张
$src = Imgsrc::src($html);              // /uploads/a.png

// 第二张
$src = Imgsrc::src($html, 2);           // https://cdn.example.com/b.png

// 最后一张
$src = Imgsrc::last($html);             // /uploads/c.png

// 从第 2 张开始，取 2 张
$list = Imgsrc::srcList($html, 2, 2);   // ['https://cdn.example.com/b.png', '/uploads/c.png']

// 全部图片
$list = Imgsrc::all($html);

// 图片数量
$count = Imgsrc::count($html);
```

### 地址黑名单

```php
// 排除包含 avatar 的地址
$list = Imgsrc::all($html, 'avatar');

// 排除多个关键字
$list = Imgsrc::all($html, ['avatar', 'emoji']);
```

### 懒加载图片

默认属性顺序为 `src`、`data-src`、`data-original`、`data-lazy-src`。当占位图写在 `src`、真实地址写在 `data-src` 时，应调整优先级：

```php
Imgsrc::config([
    'attrs' => ['data-src', 'data-original', 'src'],
]);

$src = Imgsrc::src('<img src="/placeholder.gif" data-src="/real.png">');
// /real.png
```

### 绝对地址

```php
Imgsrc::config([
    'base_url' => 'https://www.example.com/blog',
    'absolute' => true,
]);

Imgsrc::src('<img src="uploads/a.png">');  // https://www.example.com/blog/uploads/a.png
Imgsrc::src('<img src="/uploads/a.png">'); // https://www.example.com/uploads/a.png
```

也可以直接调用：

```php
$url = Imgsrc::resolve('/uploads/a.png', 'https://www.example.com');
// https://www.example.com/uploads/a.png

$html = Imgsrc::toAbsolute($html, 'https://www.example.com');
```

### 批量替换地址

```php
$html = Imgsrc::replace($html, function (string $src, string $tag): string {
    return str_starts_with($src, 'http') ? $src : 'https://cdn.example.com' . $src;
});
```

## ThinkPHP 门面

```php
use youvision\thinkimgsrc\facade\Imgsrc;

$src = Imgsrc::src($html);
$list = Imgsrc::all($html);
```

## 独立使用

核心类不依赖 ThinkPHP，可以单独引入：

```php
require 'src/Imgsrc.php';

use youvision\thinkimgsrc\Imgsrc;

$src = Imgsrc::src($html);
```

## API

| 方法 | 返回值 | 说明 |
| --- | --- | --- |
| `src($html, $num = 1, $order = null, $blacklist = null, $model = null)` | `string|null` | 获取指定序号图片 |
| `srcList($html, $startNum = 1, $length = 0, $order = null, $blacklist = null, $model = null)` | `array` | 获取指定区间图片列表 |
| `all($html, $blacklist = null, $order = null)` | `array` | 获取全部图片 |
| `first($html, $blacklist = null)` | `string|null` | 获取第一张图片 |
| `last($html, $blacklist = null)` | `string|null` | 获取最后一张图片 |
| `count($html, $blacklist = null, $order = null)` | `int` | 统计去重后的图片数量 |
| `replace($html, $handler)` | `string` | 批量替换图片地址 |
| `resolve($url, $baseUrl = null)` | `string` | 将单个地址补全为绝对地址 |
| `toAbsolute($html, $baseUrl = null)` | `string` | 将 HTML 中图片地址补全为绝对地址 |
| `config($config = null)` | `array` | 读取或合并运行配置 |
| `reset()` | `void` | 恢复默认配置 |

参数说明：

- `$order`：`asc` 正序，`desc` 倒序
- `$blacklist`：字符串或数组；传 `false` 关闭黑名单，传 `null` 使用配置
- `$model`：`string` 或 `preg`；传 `null` 使用配置
- `$startNum`：从 1 开始
- `$length`：`0` 表示提取到末尾

## 与 tcwei/imgsrc 的差异

本包参考 [ITzhiwei/getImgSrc](https://github.com/ITzhiwei/getImgSrc) 的能力重新设计：

- 包名与命名空间改为 `youvision/think-imgsrc` / `youvision\thinkimgsrc`
- 适配 ThinkPHP 8，提供服务注册、配置合并与门面
- 统一 PHP 8 类型声明与返回值
- 修复批量提取时数组黑名单判断错误
- 修复正则未匹配到 `src` 时的下标访问问题
- 修复属性值包含 `>` 时标签截断问题
- 增加懒加载属性、去重、数量限制、绝对地址补全与批量替换

## 测试

```bash
composer install
vendor/bin/phpunit
```

## License

MIT