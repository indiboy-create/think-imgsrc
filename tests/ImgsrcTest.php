<?php
declare(strict_types=1);

namespace youvision\thinkimgsrc\tests;

use PHPUnit\Framework\TestCase;
use youvision\thinkimgsrc\Imgsrc;

final class ImgsrcTest extends TestCase
{
    protected function setUp(): void
    {
        Imgsrc::reset();
    }

    public function testSrcAndOrder(): void
    {
        $html = '<p>正文</p><img src="/a.png"><img src="/b.png"><img src="/c.png">';

        $this->assertSame('/a.png', Imgsrc::src($html));
        $this->assertSame('/b.png', Imgsrc::src($html, 2));
        $this->assertSame('/c.png', Imgsrc::src($html, 1, 'desc'));
        $this->assertNull(Imgsrc::src($html, 4));
    }

    public function testQuotesTagBoundaryAndUnquoted(): void
    {
        $html = '<img alt="a > b" src=\'/single.png\'>'
            . '<IMG SRC=/plain.png>'
            . '<img src = "space.png">';

        $this->assertSame('/single.png', Imgsrc::src($html, 1));
        $this->assertSame('/plain.png', Imgsrc::src($html, 2));
        $this->assertSame('space.png', Imgsrc::src($html, 3));
    }

    public function testSrcListRange(): void
    {
        $html = '<img src="/1.png"><img src="/2.png"><img src="/3.png"><img src="/4.png">';

        $this->assertSame(['/2.png', '/3.png'], Imgsrc::srcList($html, 2, 2));
        $this->assertSame(['/3.png', '/4.png'], Imgsrc::srcList($html, 3, 0));
        $this->assertSame([], Imgsrc::srcList($html, 9, 2));
    }

    public function testBlacklist(): void
    {
        $html = '<img src="/a.png"><img src="/ad.png"><img src="/b.png">';

        $this->assertSame('/b.png', Imgsrc::src($html, 2, 'asc', 'ad'));
        $this->assertSame(['/a.png', '/b.png'], Imgsrc::all($html, ['ad']));
    }

    public function testLazyAttributes(): void
    {
        Imgsrc::config(['attrs' => ['data-src', 'data-original', 'src']]);

        $html = '<img src="/placeholder.gif" data-src="/real.png">';
        $this->assertSame('/real.png', Imgsrc::src($html));
    }

    public function testDedupeAndLimit(): void
    {
        $html = '<img src="/a.png"><img src="/a.png"><img src="/b.png"><img src="/c.png">';

        $this->assertSame(['/a.png', '/b.png', '/c.png'], Imgsrc::all($html));

        Imgsrc::config(['limit' => 2]);
        $this->assertSame(['/a.png', '/b.png'], Imgsrc::all($html));

        Imgsrc::config(['dedupe' => false, 'limit' => 0]);
        $this->assertSame(['/a.png', '/a.png', '/b.png', '/c.png'], Imgsrc::all($html));
    }

    public function testAbsoluteAndProtocolRelative(): void
    {
        Imgsrc::config([
            'base_url' => 'https://www.example.com/blog',
            'absolute' => true,
        ]);

        $this->assertSame(
            'https://www.example.com/blog/a.png',
            Imgsrc::src('<img src="a.png">')
        );
        $this->assertSame(
            'https://www.example.com/a.png',
            Imgsrc::src('<img src="/a.png">')
        );
        $this->assertSame(
            'https://cdn.example.com/a.png',
            Imgsrc::src('<img src="//cdn.example.com/a.png">')
        );
        $this->assertSame(
            'https://cdn.example.com/a.png#hash',
            Imgsrc::src('<img src="https://cdn.example.com/a.png#hash">')
        );
    }

    public function testReplace(): void
    {
        $html = '<p><img src="/a.png" alt="A"></p><img data-src="/b.png">';

        $result = Imgsrc::replace($html, static fn (string $src): string => 'https://cdn.example.com' . $src);

        $this->assertSame(
            '<p><img src="https://cdn.example.com/a.png" alt="A"></p>'
            . '<img data-src="https://cdn.example.com/b.png">',
            $result
        );
    }

    public function testToAbsolute(): void
    {
        $html = '<img src="a.png"><img src="https://cdn.example.com/b.png">';

        $this->assertSame(
            '<img src="https://www.example.com/blog/a.png">'
            . '<img src="https://cdn.example.com/b.png">',
            Imgsrc::toAbsolute($html, 'https://www.example.com/blog')
        );
    }

    public function testCountAndAliases(): void
    {
        $html = '<img src="/a.png"><img src="/b.png">';

        $this->assertSame(2, Imgsrc::count($html));
        $this->assertSame('/a.png', Imgsrc::first($html));
        $this->assertSame('/b.png', Imgsrc::last($html));
    }

    public function testEmptyAndInvalidInput(): void
    {
        $this->assertNull(Imgsrc::src(''));
        $this->assertNull(Imgsrc::src(null));
        $this->assertSame([], Imgsrc::srcList(''));
        $this->assertNull(Imgsrc::src('<image href="/a.svg">'));
        $this->assertNull(Imgsrc::src('<p>没有图片</p>'));
    }

    public function testConfigMergeAndReset(): void
    {
        Imgsrc::config(['model' => 'preg', 'limit' => 3]);

        $config = Imgsrc::config();
        $this->assertSame('preg', $config['model']);
        $this->assertSame(3, $config['limit']);
        $this->assertSame('asc', $config['order']);

        Imgsrc::reset();
        $this->assertSame('string', Imgsrc::config()['model']);
        $this->assertSame(0, Imgsrc::config()['limit']);
    }
}