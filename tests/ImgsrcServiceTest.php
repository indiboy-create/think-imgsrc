<?php
declare(strict_types=1);

namespace youvision\thinkimgsrc\tests;

use PHPUnit\Framework\TestCase;
use think\App;
use youvision\thinkimgsrc\Imgsrc;
use youvision\thinkimgsrc\ImgsrcService;
use youvision\thinkimgsrc\facade\Imgsrc as ImgsrcFacade;

final class ImgsrcServiceTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        Imgsrc::reset();
        $this->app = new App();
    }

    public function testServiceMergesConfigAndBindsContainer(): void
    {
        $this->app->config->set([
            'limit' => 5,
            'attrs' => ['data-src', 'src'],
        ], 'imgsrc');

        (new ImgsrcService($this->app))->register();

        $config = $this->app->config->get('imgsrc');
        $this->assertSame('string', $config['model']);
        $this->assertSame(5, $config['limit']);
        $this->assertSame(['data-src', 'src'], $config['attrs']);
        $this->assertSame(5, Imgsrc::config()['limit']);
        $this->assertInstanceOf(Imgsrc::class, $this->app->make('imgsrc'));
    }

    public function testFacadeUsesCoreClass(): void
    {
        (new ImgsrcService($this->app))->register();
        Imgsrc::config([
            'base_url' => 'https://www.example.com',
            'absolute' => true,
        ]);

        $html = '<img src="/a.png"><img data-src="b.png">';

        $this->assertSame('https://www.example.com/a.png', ImgsrcFacade::src($html));
        $this->assertSame(
            ['https://www.example.com/a.png', 'https://www.example.com/b.png'],
            ImgsrcFacade::all($html)
        );
    }
}