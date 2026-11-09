<?php
declare(strict_types=1);

namespace youvision\thinkimgsrc\facade;

use think\Facade;

/**
 * @method static string|null src(?string $html, int $num = 1, string|null $order = null, array|string|false|null $blacklist = null, string|null $model = null)
 * @method static array srcList(?string $html, int $startNum = 1, int $length = 0, string|null $order = null, array|string|false|null $blacklist = null, string|null $model = null)
 * @method static array all(?string $html, array|string|false|null $blacklist = null, string|null $order = null)
 * @method static string|null first(?string $html, array|string|false|null $blacklist = null)
 * @method static string|null last(?string $html, array|string|false|null $blacklist = null)
 * @method static int count(?string $html, array|string|false|null $blacklist = null, string|null $order = null)
 * @method static string replace(string $html, callable $handler)
 * @method static string resolve(string $url, string|null $baseUrl = null)
 * @method static string toAbsolute(string $html, string|null $baseUrl = null)
 * @method static array config(array|null $config = null)
 * @method static void reset()
 */
class Imgsrc extends Facade
{
    protected static function getFacadeClass()
    {
        return \youvision\thinkimgsrc\Imgsrc::class;
    }
}