<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Config;
use App\Model\Api\Member;
use Illuminate\Http\Request;

/**
 * 网站配置
 */
class ConfigController extends Controller
{
    use FormatTrait;

    /**
     * 获取网站名称
     * @param Request $request
     */
    public function getSite(Request $request, Config $mConfig)
    {
        $info = $mConfig->where('name', 'site')->first();
        $info = $this->dbResult($info);

        $site = '聚客';
        if (!empty($info)) {
            $site = $info['content'];
        }

        return $this->jsonAdminResult([
            'data' => $site
        ]);
    }

    /**
     * 获取国学视频
     * @param Request $request
     */
    public function getGrade(Request $request, Member $mMember, Config $mConfig)
    {
        $info = $mConfig->where('name', 'grade')->first();
        $info = $this->dbResult($info);

        $content = [];
        if (!empty($info)) {
            $content = json_decode($info['content'], true) ?? [];
        }

        return $this->jsonAdminResult([
            'list' => $content
        ]);
    }
}
