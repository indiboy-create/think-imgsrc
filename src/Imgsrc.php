<?php
declare(strict_types=1);

namespace youvision\thinkimgsrc;

/**
 * HTML 图片地址提取器
 *
 * 核心类不依赖 ThinkPHP 容器，可独立使用；在 ThinkPHP 8 中由服务类
 * 自动合并配置并注册到容器。
 */
final class Imgsrc
{
    public const MODEL_STRING = 'string';
    public const MODEL_PREG = 'preg';

    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';

    /**
     * 匹配完整的 img 标签，允许属性值中出现 > 字符
     */
    private const IMG_TAG_PATTERN = '/<img\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/is';

    private const DEFAULTS = [
        'model' => self::MODEL_STRING,
        'order' => self::ORDER_ASC,
        'attrs' => ['src', 'data-src', 'data-original', 'data-lazy-src'],
        'blacklist' => [],
        'base_url' => '',
        'absolute' => false,
        'dedupe' => true,
        'protocol_relative' => true,
        'protocol' => 'https',
        'limit' => 0,
    ];

    private static array $config = self::DEFAULTS;

    /**
     * 读取或设置运行配置
     *
     * @param array|null $config 传入数组时与当前配置合并，传入 null 时返回当前配置
     * @return array 合并后的完整配置
     */
    public static function config(?array $config = null): array
    {
        if ($config === null) {
            return self::$config;
        }

        self::$config = self::normalizeConfig(array_merge(self::$config, $config));

        return self::$config;
    }

    /**
     * 恢复默认配置
     */
    public static function reset(): void
    {
        self::$config = self::DEFAULTS;
    }

    /**
     * 获取指定序号的图片地址
     *
     * @param string|null $html HTML 字符串
     * @param int $num 第几张图片，从 1 开始
     * @param string|null $order asc 正序、desc 倒序，null 使用配置
     * @param array|string|false|null $blacklist 地址黑名单，null 使用配置，false 表示不启用
     * @param string|null $model string 字符串模式、preg 正则模式，null 使用配置
     * @return string|null 图片地址，未找到时返回 null
     */
    public static function src(
        ?string $html,
        int $num = 1,
        ?string $order = null,
        array|string|false|null $blacklist = null,
        ?string $model = null
    ): ?string {
        $html = (string) $html;
        if ($html === '') {
            return null;
        }

        $config = self::$config;
        $order = self::normalizeOrder($order ?? $config['order']);
        $model = self::normalizeModel($model ?? $config['model']);
        $blacklist = $blacklist === null
            ? $config['blacklist']
            : self::normalizeBlacklist($blacklist);
        $num = max(1, $num);

        if (
            $model === self::MODEL_STRING
            && $order === self::ORDER_ASC
            && $num === 1
            && $blacklist === []
        ) {
            $src = self::firstByString($html, $config['attrs']);

            return $src === null ? null : self::normalizeUrl($src, $config);
        }

        $list = self::normalizeList(
            self::collect($html, $order, $blacklist, $config['attrs']),
            $config
        );
        $src = $list[$num - 1] ?? null;

        return $src;
    }

    /**
     * 获取图片地址列表
     *
     * @param string|null $html HTML 字符串
     * @param int $startNum 起始序号，从 1 开始
     * @param int $length 提取数量，0 表示提取到末尾
     * @param string|null $order asc 正序、desc 倒序，null 使用配置
     * @param array|string|false|null $blacklist 地址黑名单，null 使用配置，false 表示不启用
     * @param string|null $model 兼容参数，集合提取统一使用正则解析
     * @return array 图片地址列表，未找到时返回空数组
     */
    public static function srcList(
        ?string $html,
        int $startNum = 1,
        int $length = 0,
        ?string $order = null,
        array|string|false|null $blacklist = null,
        ?string $model = null
    ): array {
        $html = (string) $html;
        if ($html === '') {
            return [];
        }

        $config = self::$config;
        $order = self::normalizeOrder($order ?? $config['order']);
        $blacklist = $blacklist === null
            ? $config['blacklist']
            : self::normalizeBlacklist($blacklist);

        $list = self::normalizeList(
            self::collect($html, $order, $blacklist, $config['attrs']),
            $config
        );
        if ($list === []) {
            return [];
        }

        $list = array_slice($list, max(0, $startNum - 1), $length > 0 ? $length : null);

        if ($config['limit'] > 0) {
            $list = array_slice($list, 0, $config['limit']);
        }

        return $list;
    }

    /**
     * 获取全部图片地址
     *
     * @param string|null $html HTML 字符串
     * @param array|string|false|null $blacklist 地址黑名单
     * @param string|null $order asc 正序、desc 倒序
     * @return array 图片地址列表
     */
    public static function all(
        ?string $html,
        array|string|false|null $blacklist = null,
        ?string $order = null
    ): array {
        return self::srcList($html, 1, 0, $order, $blacklist, self::MODEL_PREG);
    }

    /**
     * 获取第一张图片地址
     */
    public static function first(
        ?string $html,
        array|string|false|null $blacklist = null
    ): ?string {
        return self::src($html, 1, self::ORDER_ASC, $blacklist, self::MODEL_STRING);
    }

    /**
     * 获取最后一张图片地址
     */
    public static function last(
        ?string $html,
        array|string|false|null $blacklist = null
    ): ?string {
        return self::src($html, 1, self::ORDER_DESC, $blacklist, self::MODEL_PREG);
    }

    /**
     * 统计图片数量（去重后的数量）
     */
    public static function count(
        ?string $html,
        array|string|false|null $blacklist = null,
        ?string $order = null
    ): int {
        $html = (string) $html;
        if ($html === '') {
            return 0;
        }

        $config = self::$config;
        $order = self::normalizeOrder($order ?? $config['order']);
        $blacklist = $blacklist === null
            ? $config['blacklist']
            : self::normalizeBlacklist($blacklist);

        return count(self::normalizeList(
            self::collect($html, $order, $blacklist, $config['attrs']),
            $config
        ));
    }

    /**
     * 批量替换图片地址
     *
     * @param string $html HTML 字符串
     * @param callable $handler 回调，参数为原图片地址与 img 标签，返回新地址
     * @return string 替换后的 HTML
     */
    public static function replace(string $html, callable $handler): string
    {
        if ($html === '') {
            return $html;
        }

        $result = preg_replace_callback(
            self::IMG_TAG_PATTERN,
            static function (array $match) use ($handler): string {
                $tag = $match[0];
                $matched = self::matchAttr($tag, self::$config['attrs']);
                if ($matched === null) {
                    return $tag;
                }

                [$attr, $src] = $matched;
                $newSrc = $handler($src, $tag);
                if (!is_string($newSrc) || $newSrc === '' || $newSrc === $src) {
                    return $tag;
                }

                $pattern = '/(?<![\w:-])(' . preg_quote($attr, '/')
                    . '\s*=\s*)(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)/i';
                $replaced = preg_replace_callback(
                    $pattern,
                    static fn (array $m): string => $m[1] . '"'
                        . str_replace('"', '&quot;', $newSrc) . '"',
                    $tag,
                    1
                );

                return $replaced ?? $tag;
            },
            $html
        );

        return $result ?? $html;
    }

    /**
     * 将单个图片地址补全为绝对地址
     *
     * @param string $url 图片地址
     * @param string|null $baseUrl 基准地址，null 使用配置中的 base_url
     */
    public static function resolve(string $url, ?string $baseUrl = null): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        $config = self::$config;

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return $config['protocol_relative'] ? $config['protocol'] . ':' . $url : $url;
        }

        $base = rtrim(trim((string) ($baseUrl ?? $config['base_url'])), '/');
        if ($base === '') {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            if (preg_match('#^([a-z][a-z0-9+.-]*:)?//([^/]+)#i', $base, $match)) {
                $scheme = $match[1] !== '' ? $match[1] : $config['protocol'] . ':';

                return $scheme . '//' . $match[2] . $url;
            }

            return $base . $url;
        }

        return $base . '/' . $url;
    }

    /**
     * 将 HTML 中全部图片地址补全为绝对地址
     *
     * @param string $html HTML 字符串
     * @param string|null $baseUrl 基准地址，null 使用配置中的 base_url
     * @return string 处理后的 HTML
     */
    public static function toAbsolute(string $html, ?string $baseUrl = null): string
    {
        return self::replace($html, static fn (string $src): string => self::resolve($src, $baseUrl));
    }

    /**
     * 按顺序提取全部图片地址
     */
    private static function collect(string $html, string $order, array $blacklist, array $attrs): array
    {
        if (!preg_match_all(self::IMG_TAG_PATTERN, $html, $matches)) {
            return [];
        }

        $tags = $matches[0];
        if ($order === self::ORDER_DESC) {
            $tags = array_reverse($tags);
        }

        $result = [];
        foreach ($tags as $tag) {
            $matched = self::matchAttr($tag, $attrs);
            if ($matched === null) {
                continue;
            }

            if (self::isBlacklisted($matched[1], $blacklist)) {
                continue;
            }

            $result[] = $matched[1];
        }

        return $result;
    }

    /**
     * 字符串模式下的首图提取
     */
    private static function firstByString(string $html, array $attrs): ?string
    {
        $offset = 0;
        $length = strlen($html);

        while ($offset < $length) {
            $position = stripos($html, '<img', $offset);
            if ($position === false) {
                return null;
            }

            $end = self::tagEnd($html, $position + 4);
            if ($end === false) {
                return null;
            }

            $tag = substr($html, $position, $end - $position + 1);
            $matched = self::matchAttr($tag, $attrs);
            if ($matched !== null) {
                return $matched[1];
            }

            $offset = $end + 1;
        }

        return null;
    }

    /**
     * 定位标签结束位置，忽略引号内的 > 字符
     */
    private static function tagEnd(string $html, int $start): int|false
    {
        $length = strlen($html);
        $quote = '';

        for ($i = $start; $i < $length; $i++) {
            $char = $html[$i];

            if ($quote !== '') {
                if ($char === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;

                continue;
            }

            if ($char === '>') {
                return $i;
            }
        }

        return false;
    }

    /**
     * 在标签中按优先级匹配图片地址属性
     *
     * @return array{0: string, 1: string}|null [属性名, 属性值]
     */
    private static function matchAttr(string $tag, array $attrs): ?array
    {
        foreach ($attrs as $attr) {
            $pattern = '/(?<![\w:-])(' . preg_quote($attr, '/')
                . '\s*=\s*)(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i';

            if (!preg_match($pattern, $tag, $match)) {
                continue;
            }

            $value = '';
            foreach ([2, 3, 4] as $index) {
                if (isset($match[$index]) && $match[$index] !== '') {
                    $value = $match[$index];
                    break;
                }
            }

            if ($value !== '') {
                return [$attr, $value];
            }
        }

        return null;
    }

    private static function isBlacklisted(string $src, array $blacklist): bool
    {
        foreach ($blacklist as $item) {
            if ($item !== '' && str_contains($src, $item)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeList(array $list, array $config): array
    {
        if ($list === []) {
            return [];
        }

        $list = array_map(
            static fn (string $src): string => self::normalizeUrl($src, $config),
            $list
        );

        if ($config['dedupe']) {
            $list = array_values(array_unique($list));
        }

        return $list;
    }

    private static function normalizeUrl(string $url, array $config): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return $config['protocol_relative'] ? $config['protocol'] . ':' . $url : $url;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return $url;
        }

        if (!$config['absolute'] || $config['base_url'] === '') {
            return $url;
        }

        return self::resolve($url, $config['base_url']);
    }

    private static function normalizeConfig(array $config): array
    {
        $config = array_merge(self::DEFAULTS, $config);

        $config['model'] = self::normalizeModel(
            is_string($config['model']) ? $config['model'] : ''
        );
        $config['order'] = self::normalizeOrder(
            is_string($config['order']) ? $config['order'] : ''
        );

        $attrs = is_array($config['attrs']) ? $config['attrs'] : [$config['attrs']];
        $attrs = array_values(array_unique(array_filter(
            array_map(
                static fn ($attr): string => is_string($attr) ? trim($attr) : '',
                $attrs
            ),
            static fn (string $attr): bool => $attr !== ''
        )));
        $config['attrs'] = $attrs !== [] ? $attrs : ['src'];

        $config['blacklist'] = self::normalizeBlacklist($config['blacklist']);

        $config['base_url'] = is_string($config['base_url'])
            ? rtrim(trim($config['base_url']), '/')
            : '';
        $config['protocol'] = is_string($config['protocol'])
            && strtolower(trim($config['protocol'])) === 'http' ? 'http' : 'https';
        $config['absolute'] = self::toBool($config['absolute']);
        $config['dedupe'] = self::toBool($config['dedupe']);
        $config['protocol_relative'] = self::toBool($config['protocol_relative']);
        $config['limit'] = is_numeric($config['limit']) ? max(0, (int) $config['limit']) : 0;

        return $config;
    }

    private static function normalizeBlacklist(mixed $blacklist): array
    {
        if ($blacklist === false || $blacklist === null || $blacklist === '') {
            return [];
        }

        if (is_string($blacklist) || is_numeric($blacklist)) {
            $blacklist = trim((string) $blacklist);

            return $blacklist === '' ? [] : [$blacklist];
        }

        if (!is_array($blacklist)) {
            return [];
        }

        $result = [];
        foreach ($blacklist as $item) {
            if (is_scalar($item) && (string) $item !== '') {
                $result[] = (string) $item;
            }
        }

        return array_values(array_unique($result));
    }

    private static function normalizeOrder(string $order): string
    {
        return strtolower($order) === self::ORDER_DESC ? self::ORDER_DESC : self::ORDER_ASC;
    }

    private static function normalizeModel(string $model): string
    {
        return strtolower($model) === self::MODEL_PREG ? self::MODEL_PREG : self::MODEL_STRING;
    }

    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }
}