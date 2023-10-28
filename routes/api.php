<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*********************************************/

$dingoApi = app(\Dingo\Api\Routing\Router::class);
$dingoApi->version("v1", [
    "middleware" => ["AdminToken", "CrossHttp"]
], function ($dingoApi) {
    // 上传文件
    $dingoApi->post("service/uploadFile", \App\Http\Controllers\Admin\ServiceController::class."@uploadFile")->name("service.uploadFile");

    // 轮播图管理
    $dingoApi->get("mobile/slide/list", \App\Http\Controllers\Admin\Mobile\SlideController::class."@list")->name("mobile.slide.list");
    $dingoApi->post("mobile/slide/add", \App\Http\Controllers\Admin\Mobile\SlideController::class."@add")->name("mobile.slide.add");
    $dingoApi->post("mobile/slide/edit", \App\Http\Controllers\Admin\Mobile\SlideController::class."@edit")->name("mobile.slide.edit");
    $dingoApi->post("mobile/slide/del", \App\Http\Controllers\Admin\Mobile\SlideController::class."@del")->name("mobile.slide.del");

    // 平台说明管理
    $dingoApi->get("mobile/article/list", \App\Http\Controllers\Admin\Mobile\ArticleController::class."@list")->name("mobile.article.list");
    $dingoApi->post("mobile/article/add", \App\Http\Controllers\Admin\Mobile\ArticleController::class."@add")->name("mobile.article.add");
    $dingoApi->post("mobile/article/edit", \App\Http\Controllers\Admin\Mobile\ArticleController::class."@edit")->name("mobile.article.edit");
    $dingoApi->post("mobile/article/del", \App\Http\Controllers\Admin\Mobile\ArticleController::class."@del")->name("mobile.article.del");

    // 网站配置管理
    $dingoApi->get("mobile/config/list", \App\Http\Controllers\Admin\Mobile\ConfigController::class."@list")->name("mobile.config.list");
    $dingoApi->post("mobile/config/saveConfig", \App\Http\Controllers\Admin\Mobile\ConfigController::class."@saveConfig")->name("mobile.config.saveConfig");

    // 会员管理
    $dingoApi->get("mobile/member/list", \App\Http\Controllers\Admin\Mobile\MemberController::class."@list")->name("mobile.member.list");
    $dingoApi->post("mobile/member/edit", \App\Http\Controllers\Admin\Mobile\MemberController::class."@edit")->name("mobile.member.edit");
    $dingoApi->post("mobile/member/createSystemMember", \App\Http\Controllers\Admin\Mobile\MemberController::class."@createSystemMember")->name("mobile.member.createSystemMember");

    // 打款记录
    $dingoApi->get("mobile/payRecord/list", \App\Http\Controllers\Admin\Mobile\PayRecordController::class."@list")->name("mobile.payRecord.list");
    $dingoApi->post("mobile/payRecord/add", \App\Http\Controllers\Admin\Mobile\PayRecordController::class."@add")->name("mobile.payRecord.add");
    $dingoApi->post("mobile/payRecord/edit", \App\Http\Controllers\Admin\Mobile\PayRecordController::class."@edit")->name("mobile.payRecord.edit");
    $dingoApi->post("mobile/payRecord/del", \App\Http\Controllers\Admin\Mobile\PayRecordController::class."@del")->name("mobile.payRecord.del");

    // 用户
    $dingoApi->post("users/checkName", \App\Http\Controllers\Admin\System\UserController::class."@checkName")->name("users.checkName");
    $dingoApi->put("users/pwd", \App\Http\Controllers\Admin\System\UserController::class."@changePwd")->name("users.changePwd");
    $dingoApi->delete("users/batch", \App\Http\Controllers\Admin\System\UserController::class."@batchDestroy")->name("users.batchDestroy"); # 非resource应该放在resource上面
    $dingoApi->Resource("users", \App\Http\Controllers\Admin\System\UserController::class);

    // 权限
    $dingoApi->patch("permissions/{id}", \App\Http\Controllers\Admin\System\PermissionController::class."@edit")->name("permissions.edit");
    $dingoApi->get("permissions/total", \App\Http\Controllers\Admin\System\PermissionController::class."@total")->name("permissions.total");
    $dingoApi->get("permissions", \App\Http\Controllers\Admin\System\PermissionController::class."@index")->name("permissions.index");
    $dingoApi->put("permissions", \App\Http\Controllers\Admin\System\PermissionController::class."@update")->name("permissions.update");

    // 角色
    $dingoApi->get("roles/total", \App\Http\Controllers\Admin\System\RoleController::class."@total")->name("roles.total");
    $dingoApi->delete("roles/batch", \App\Http\Controllers\Admin\System\RoleController::class."@batchDestroy")->name("roles.batchDestroy");
    $dingoApi->Resource("roles", \App\Http\Controllers\Admin\System\RoleController::class);

    // 系统操作日志
    $dingoApi->get("logs", \App\Http\Controllers\Admin\System\LogController::class."@index")->name("logs.index");

    // 用户授权令牌 - 销毁
    $dingoApi->delete("tokens/{role}", \App\Http\Controllers\Admin\System\TokenController::class."@destroy")->name("tokens.destroy");

});

$dingoApi->version("v1", [
    "middleware" => ["CrossHttp"]
], function ($dingoApi) {
    // 用户授权令牌 - 获取
    $dingoApi->post("tokens", \App\Http\Controllers\Admin\System\TokenController::class."@store")->name("tokens.store");
});

// mobile端
$dingoApi->version("v1", [
    "middleware" => ["CrossHttp"]
], function ($dingoApi) {
    // 用户注册
    $dingoApi->post("api/user/register", \App\Http\Controllers\Api\MemberController::class."@register")->name("api.user.register");

    // 用户登录
    $dingoApi->post("api/user/login", \App\Http\Controllers\Api\MemberController::class."@login")->name("api.user.login");

    // 忘记密码
    $dingoApi->post("api/user/forget", \App\Http\Controllers\Api\MemberController::class."@forget")->name("api.user.forget");

    // 验证码
    $dingoApi->Get("api/captchas/{id}", \App\Http\Controllers\Api\CaptchaController::class."@index")->name("api.captchas.index");
    $dingoApi->Post("api/captchas/check", \App\Http\Controllers\Api\CaptchaController::class."@check")->name("api.captchas.check");

    // 网站名称
    $dingoApi->get("api/config/getSite", \App\Http\Controllers\Api\ConfigController::class."@getSite")->name("api.config.getSite");
    // 国学视频
    $dingoApi->get("api/config/getGrade", \App\Http\Controllers\Api\ConfigController::class."@getGrade")->name("api.config.getGrade");
});

$dingoApi->version("v1", [
    "middleware" => ["ApiToken", "CrossHttp"]
], function ($dingoApi) {
    // 上传文件
    $dingoApi->post("api/service/uploadFile", \App\Http\Controllers\Api\ServiceController::class."@uploadFile")->name("api.service.uploadFile");

    // 首页
    $dingoApi->get("api/index/list", \App\Http\Controllers\Api\IndexController::class."@list")->name("api.index.list");

    // 退出登录
    $dingoApi->post("api/user/logout", \App\Http\Controllers\Api\MemberController::class."@logout")->name("api.user.logout");
    // 获取用户信息
    $dingoApi->get("api/user/getMember", \App\Http\Controllers\Api\MemberController::class."@getMember")->name("api.user.getMember");
    // 编辑用户信息
    $dingoApi->post("api/user/editMember", \App\Http\Controllers\Api\MemberController::class."@editMember")->name("api.user.editMember");

    // 收款方式
    $dingoApi->post("api/user/payment", \App\Http\Controllers\Api\MemberController::class."@payment")->name("api.user.payment");
    // 获取收款方式
    $dingoApi->get("api/user/getPayment", \App\Http\Controllers\Api\MemberController::class."@getPayment")->name("api.user.getPayment");

    // 推荐人信息
    $dingoApi->get("api/user/getInvite", \App\Http\Controllers\Api\MemberController::class."@getInvite")->name("api.user.getInvite");

    // 我的收益
    $dingoApi->get("api/user/getMoneyList", \App\Http\Controllers\Api\MemberController::class."@getMoneyList")->name("api.user.getMoneyList");

    // 同修层级用户数量
    $dingoApi->get("api/our/getOurLevelNum", \App\Http\Controllers\Api\OurController::class."@getOurLevelNum")->name("api.our.getOurLevelNum");
    // 同修小组列表
    $dingoApi->get("api/our/getGroupList", \App\Http\Controllers\Api\OurController::class."@getGroupList")->name("api.our.getGroupList");

    // 获取上级用户
    $dingoApi->get("api/up/getLevelUpMember", \App\Http\Controllers\Api\UpController::class."@getLevelUpMember")->name("api.up.getLevelUpMember");
    $dingoApi->post("api/up/levelUp", \App\Http\Controllers\Api\UpController::class."@levelUp")->name("api.up.levelUp");
    $dingoApi->get("api/up/getPayRecordList", \App\Http\Controllers\Api\UpController::class."@getPayRecordList")->name("api.up.getPayRecordList");
    $dingoApi->get("api/up/getApplyList", \App\Http\Controllers\Api\UpController::class."@getApplyList")->name("api.up.getApplyList");
    $dingoApi->get("api/up/getPayRecord", \App\Http\Controllers\Api\UpController::class."@getPayRecord")->name("api.up.getPayRecord");
    $dingoApi->post("api/up/upVerify", \App\Http\Controllers\Api\UpController::class."@upVerify")->name("api.up.upVerify");
});


