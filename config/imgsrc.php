<?php

// +----------------------------------------------------------------------
// | think-imgsrc 配置文件
// | 所有配置项均可只在项目 config/imgsrc.php 中覆盖需要修改的部分，
// | 未覆盖的配置项自动使用本文件中的默认值。
// +----------------------------------------------------------------------

return [
    // 默认提取模式
    // string：字符串定位模式，单张提取速度更快，适合获取首图
    // preg  ：正则模式，批量提取、倒序或数量较大时更稳定
    'model' => 'string',

    // 默认提取顺序
    // asc ：从前往后计数
    // desc：从后往前计数
    'order' => 'asc',

    // 图片地址属性及优先级
    // 按数组顺序依次匹配，命中第一个非空属性即返回。
    // 使用懒加载图片时，可将 data-src、data-original 放在 src 之前。
    'attrs' => [
        'src',
        'data-src',
        'data-original',
        'data-lazy-src',
    ],

    // 图片地址黑名单
    // 地址中包含数组内任意一个字符串时即被排除，例如 ['avatar', 'emoji']。
    // 传入空数组表示不启用黑名单。
    'blacklist' => [],

    // 相对地址补全时使用的基准地址
    // 例如 https://www.example.com，留空表示不补全相对地址。
    'base_url' => '',

    // 是否自动将相对图片地址补全为绝对地址
    // 仅在 base_url 不为空时生效。
    'absolute' => false,

    // 是否对列表结果去重
    // true：相同图片地址只保留首次出现的一张。
    'dedupe' => true,

    // 是否补全协议相对地址
    // true：将 //cdn.example.com/a.png 补全为 https://cdn.example.com/a.png。
    'protocol_relative' => true,

    // 协议相对地址补全时使用的协议
    // 可选值：http、https。
    'protocol' => 'https',

    // 列表方法最多返回的图片数量
    // 0 表示不限制；仅作用于 srcList、all 等列表方法。
    'limit' => 0,
];