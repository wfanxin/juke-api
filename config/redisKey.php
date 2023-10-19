<?php

$projectFlag = env('API_DOMAIN', ''); // redis前缀
return [
    'user_info' => ['key' => $projectFlag . '=>admin:user_info:%s'], // 用户信息
    'x_token' => ['key' => $projectFlag . '=>admin:system:token:%s', 'ttl' => 86400], // 登录授权令牌信息
    'rbac' => ['key' => $projectFlag . '=>admin:rbac:%s'], // 角色权限信息
    'captcha' => ['key' => $projectFlag . '=>admin:captcha:%s', 'ttl' => 1800],
    'control_auth' => ['key' => $projectFlag . '=>admin:control:auth', 'ttl' => 3600],

    'mem_info' => ['key' => $projectFlag . '=>mem:info:%s', 'ttl' => 86400], /// 用户信息
    'web_verify_code_mail' => ['key' => $projectFlag . '=>mem:verify_code_mail:%s', 'ttl' => 300], /// 邮箱验证码

    'm_token' => ['key' => $projectFlag.'=>mem:system:token:%s:%s'], /// 登录授权令牌信息
    'mem_appSecret_status' => ['key' => $projectFlag . '=>mem:mem_appSecret_status:%s'], /// 用户状态以及密钥信息
];
