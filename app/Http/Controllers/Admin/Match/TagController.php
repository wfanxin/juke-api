<?php

namespace App\Http\Controllers\Admin\Match;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Match;
use App\Model\Admin\Tag;
use Illuminate\Http\Request;

/**
 * @name 标签管理
 * Class TagController
 * @package App\Http\Controllers\Admin\Match
 *
 * @Resource("tags")
 */
class TagController extends Controller
{
    use FormatTrait;

    /**
     * @name 标签列表
     * @Get("/lv/match/tag/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Tag $mTag)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        // 标签大类id
        if (!empty($params['pid'])){
            $where[] = ['pid', '=', $params['pid']];
        }

        // 子类名称
        if (!empty($params['name'])){
            $where[] = ['name', 'like', '%' . $params['name'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mTag->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $tag_list = config('global.tag_list');
            $tag_list = array_column($tag_list, 'label', 'value');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['pname'] = $tag_list[$v->pid] ?? ''; // 标签大类名称
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items(),
            'tag_list' => config('global.tag_list')
        ]);
    }

    /**
     * @name 添加标签
     * @Post("/lv/match/tag/add")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function add(Request $request, Tag $mTag)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $pid = $params['pid'] ?? 0;
        $name = $params['name'] ?? '';

        if (empty($pid)) {
            return $this->jsonAdminResult([],10001, '请选择标签大类');
        }

        if (!in_array($pid, array_column(config('global.tag_list'), 'value'))) {
            return $this->jsonAdminResult([],10001, '标签大类错误');
        }

        if (empty($name)){
            return $this->jsonAdminResult([],10001, '子类名称不能为空');
        }

        $info = $mTag->where('pid', $pid)->where('name', $name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '子类名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mTag->insert([
            'pid' => $pid,
            'name' => $name,
            'created_at' => $time,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 修改标签
     * @Post("/lv/match/tag/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Tag $mTag)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $pid = $params['pid'] ?? 0;
        $name = $params['name'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($pid)) {
            return $this->jsonAdminResult([],10001, '请选择标签大类');
        }

        if (!in_array($pid, array_column(config('global.tag_list'), 'value'))) {
            return $this->jsonAdminResult([],10001, '标签大类错误');
        }

        if (empty($name)){
            return $this->jsonAdminResult([],10001, '子类名称不能为空');
        }

        $info = $mTag->where('id', '!=', $id)->where('pid', $pid)->where('name', $name)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '子类名称重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mTag->where('id', $id)->update([
            'pid' => $pid,
            'name' => $name,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除标签
     * @Post("/lv/match/tag/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Tag $mTag, Match $mMatch)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $count = $mMatch->where('tag_id', $id)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001,'标签在使用，不能删除');
        }

        $res = $mTag->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
