<?php
declare(strict_types=1);

namespace youvision\thinkimgsrc;

use think\Service;

/**
 * ThinkPHP 8 服务提供者
 */
class ImgsrcService extends Service
{
    public function register()
    {
        $default = require dirname(__DIR__) . '/config/imgsrc.php';
        $config = $this->app->config->get('imgsrc', []);

        if (!is_array($config)) {
            $config = [];
        }

        $config = array_merge($default, $config);

        $this->app->config->set($config, 'imgsrc');
        Imgsrc::config($config);
        $this->app->bind('imgsrc', Imgsrc::class);
    }
}