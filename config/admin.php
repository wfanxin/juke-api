<?php
/**
 * 后台相关配置
 */

return [
    // sort唯一
    'nav' => [
        'Mobile' => [
            'sort' => 2,
            'alias' => '网站管理'
        ],
        'System' => [
            'sort' => 3,
            'alias' => '系统管理'
        ],
    ],
    // 栏目
    'nav_show_list' => [
        '@Get:lv_mobile_slide_list',
        '@Get:lv_mobile_article_list',
        '@Get:lv_mobile_config_list',
        '@Get:lv_mobile_member_list',
        '@Get:lv_mobile_payRecord_list',
        '@Get:lv_mobile_leave_list',
        '@Get:lv_permissions',
        '@Get:lv_roles',
        '@Get:lv_users',
//        '@Get:lv_logs'
    ],
    'aliyun_oss' => [
        'AccessKeyId' => '',
        'AccessKeySecret' => '',
        'city' => '',
        'bucket' => 'img-dsg',
        'OSS_ROOT' => env('OSS_TEST',''),
        'url_pre' => 'https://img-dsg.oss-cn-shanghai.aliyuncs.com/',
        'url_pre_internal' =>'https://img-dsg.oss-cn-shanghai-internal.aliyuncs.com/',
        'headImg' =>'member/headImg/', // 用户头像路径
        'space' =>'member/space/%s/', // 用户空间
        'setting' =>'admin/setting/', // 配置图片
    ]
];
