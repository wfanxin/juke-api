<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Slide;
use App\Model\Api\Config;
use Illuminate\Http\Request;

/**
 * @name 网站配置管理
 * Class ConfigController
 * @package App\Http\Controllers\Admin\Mobile
 *
 * @Resource("configs")
 */
class ConfigController extends Controller
{
    use FormatTrait;

    /**
     * @name 网站配置
     * @Get("/lv/mobile/config/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Config $mConfig)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $site = '';
        $award = ['value' => '', 'scale' => ''];
        $grade = [];
        $level_list = config('global.level_list');
        foreach ($level_list as $value) {
            if ($value['value'] != 0) {
                $grade[] = [
                    'label' => $value['label'],
                    'money' => ''
                ];
            }
        }

        $config = $mConfig->whereIn('name', ['site', 'award', 'grade'])->get();
        $config = $this->dbResult($config);
        foreach ($config as $value) {
            if ($value['name'] == 'site') {
                $site = $value['content'];
            } else if ($value['name'] == 'award') {
                $award = json_decode($value['content'], true);
            } else if ($value['name'] == 'grade') {
                $grade = json_decode($value['content'], true);
            }
        }

        return $this->jsonAdminResult([
            'site' => $site,
            'award' => $award,
            'grade' => $grade
        ]);
    }

    /**
     * @name 保存配置
     * @Post("/lv/mobile/config/saveConfig")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function saveConfig(Request $request, Config $mConfig)
    {
        $params = $request->all();

        $site = $params['site'] ?? '';
        $award = $params['award'] ?? [];
        $grade = $params['grade'] ?? [];

        if (empty($site)) {
            return $this->jsonAdminResult([],10001, '网站名称不能为空');
        }

        if (empty($award) || empty($award['value'])) {
            return $this->jsonAdminResult([],10001, '感恩奖额度不能为空');
        }

        if (empty($award) || empty($award['scale'])) {
            return $this->jsonAdminResult([],10001, '感恩奖比例不能为空');
        }

        foreach ($grade as $value) {
            if (empty($value['money'])) {
                return $this->jsonAdminResult([],10001, '升级金额不能为空');
            }
        }

        $config = $mConfig->whereIn('name', ['site', 'award', 'grade'])->get();
        $config = $this->dbResult($config);
        $config = array_column($config,'name');

        $time = date('Y-m-d H:i:s');

        $res1 = true;
        if (in_array('site', $config)) {
            $res1 = $mConfig->where('name', 'site')->update(['content' => $site]);
        } else {
            $res1 = $mConfig->insert([
                'name' => 'site',
                'content' => $site,
                'created_at' => $time,
                'updated_at' => $time
            ]);
        }

        $res2 = true;
        if (in_array('award', $config)) {
            $res2 = $mConfig->where('name', 'award')->update(['content' => json_encode($award)]);
        } else {
            $res2 = $mConfig->insert([
                'name' => 'award',
                'content' => json_encode($award),
                'created_at' => $time,
                'updated_at' => $time
            ]);
        }

        $res3 = true;
        if (in_array('grade', $config)) {
            $res3 = $mConfig->where('name', 'grade')->update(['content' => json_encode($grade)]);
        } else {
            $res3 = $mConfig->insert([
                'name' => 'grade',
                'content' => json_encode($grade),
                'created_at' => $time,
                'updated_at' => $time
            ]);
        }

        if ($res1 && $res2 && $res3) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
