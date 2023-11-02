<?php

$projectFlag = env('API_DOMAIN', ''); // redis前缀
return [
    'user_info' => ['key' => $projectFlag . '=>admin:user_info:%s'], // 用户信息
    'x_token' => ['key' => $projectFlag . '=>admin:system:token:%s', 'ttl' => 86400], // 登录授权令牌信息
    'rbac' => ['key' => $projectFlag . '=>admin:rbac:%s'], // 角色权限信息
    'captcha' => ['key' => $projectFlag . '=>admin:captcha:%s', 'ttl' => 1800],
    'control_auth' => ['key' => $projectFlag . '=>admin:control:auth', 'ttl' => 3600],

    'mem_info' => ['key' => $projectFlag . '=>api:member_info:%s'], /// 用户信息
    'm_token' => ['key' => $projectFlag.'=>api:system:token:%s', 'ttl' => 86400], // 登录授权令牌信息
    'mem_captcha' => ['key' => $projectFlag . '=>api:captcha:%s', 'ttl' => 1800],
    'mem_code' => ['key' => $projectFlag . '=>api:code:%s', 'ttl' => 600],

    'web_verify_code_mail' => ['key' => $projectFlag . '=>api:verify_code_mail:%s', 'ttl' => 300], /// 邮箱验证码
    'mem_appSecret_status' => ['key' => $projectFlag . '=>api:mem_appSecret_status:%s'], /// 用户状态以及密钥信息
];
