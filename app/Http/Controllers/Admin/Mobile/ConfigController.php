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

        $where = [];

        $site = '';
        $grade = '';

        return $this->jsonAdminResult([
            'site' => $site,
            'grade' => $grade
        ]);
    }

    /**
     * @name 修改轮播图
     * @Post("/lv/mobile/slide/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Slide $mSlide)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $title = $params['title'] ?? '';
        $image = $params['image'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($title)) {
            return $this->jsonAdminResult([],10001, '标题不能为空');
        }

        if (empty($image)){
            return $this->jsonAdminResult([],10001, '图片不能为空');
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $image = str_replace($urlPre, '', $image);

        $time = date('Y-m-d H:i:s');
        $res = $mSlide->where('id', $id)->update([
            'id' => $id,
            'title' => $title,
            'image' => $image,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除轮播图
     * @Post("/lv/mobile/slide/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Slide $mSlide)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mSlide->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
