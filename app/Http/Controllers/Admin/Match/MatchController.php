<?php

namespace App\Http\Controllers\Admin\Match;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Match;
use App\Model\Admin\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @name 比赛管理
 * Class MatchController
 * @package App\Http\Controllers\Admin\Match
 *
 * @Resource("matchs")
 */
class MatchController extends Controller
{
    use FormatTrait;

    /**
     * @name 比赛列表
     * @Get("/lv/match/match/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Match $mMatch, Tag $mTag)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

//        $tag_sub_list = $mTag->get(['pid', 'id', 'name']);
//        $tag_sub_list = $this->dbResult($tag_sub_list);
        $tag_sub_list = DB::select('SELECT pid, id, name FROM `tags` WHERE 1 order by convert(name using gbk) asc');

        $where = [];

        if (!empty($params['pid'])) {
            $where[] = ['ptag_id', '=', $params['pid']];
        }

        // 标签大类id
        if (!empty($params['ids'])){
            $tag_id = $params['ids'];
            sort($tag_id);
            $before_tag_id = [];
            $after_tag_id = [];
            foreach ($tag_id as $value) {
                $value_arr = json_decode($value, true);
                if ($value_arr[0] == 1) {
                    $before_tag_id[] = $value;
                } else {
                    $after_tag_id[] = $value;
                }
            }

            $where[] = [function ($query) use ($tag_id, $before_tag_id, $after_tag_id) {
                $query = $query->where('tag_id', '=', '[' . implode(',', $tag_id) . ']');
                foreach ($after_tag_id as $value) {
                    $query->orWhere(function ($query) use ($before_tag_id, $value) {
                        $query->where('tag_id', 'like', '[' . implode(',', $before_tag_id) . ',[2,%')->where('tag_id', 'like', '%' . $value . '%');
                    });
                }
            }];
        }

        $total = $mMatch->where($where)->count();

        if (!empty($params['id'])) {
            $where[] = ['id', '<', $params['id']];
        }

        $orderField = 'id';
        $sort = 'desc';
        $list = $mMatch->where($where)
            ->orderBy($orderField, $sort)
            ->limit(config('global.page_size'))
            ->get();
        $list = $this->dbResult($list);

        if (!empty($list)) {
            foreach ($list as $k => $v){
                $list[$k]['tag_id'] = json_decode($v['tag_id'], true) ?? [];
                $list[$k]['match_data'] = json_decode($v['match_data'], true) ?? [];
            }
        }

        return $this->jsonAdminResult([
            'total' => $total,
            'data' => $list,
            'tag_list' => config('global.tag_list'),
            'tag_sub_list' => $tag_sub_list,
            'platform_list' => config('global.platform_list')
        ]);
    }

    /**
     * @name 添加比赛
     * @Post("/lv/match/match/add")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function add(Request $request, Match $mMatch)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $ptag_id = $params['ptag_id'] ?? 0;
        $tag_id = $params['tag_id'] ?? [];
        $match_play = trim($params['match_play'] ?? '');
        $match_score = trim($params['match_score'] ?? '');
        $match_result = trim($params['match_result'] ?? '');
        $match_half_audience = trim($params['match_half_audience'] ?? '');
        $match_type = trim($params['match_type'] ?? '');
        $match_data = $params['match_data'] ?? [];

        if (empty($tag_id)) {
            return $this->jsonAdminResult([],10001, '请选择标签');
        }

        if (empty($match_play)) {
            return $this->jsonAdminResult([],10001, '比赛场次不能为空');
        }

        if (empty($match_score)) {
            return $this->jsonAdminResult([],10001, '比分不能为空');
        }

        if (empty($match_result)) {
            return $this->jsonAdminResult([],10001, '比赛结果不能为空');
        }

        if (empty($match_half_audience)) {
            return $this->jsonAdminResult([],10001, '半全场不能为空');
        }

        if (empty($match_type)) {
            return $this->jsonAdminResult([],10001, '比赛类型不能为空');
        }

        if (empty($match_data)) {
            return $this->jsonAdminResult([],10001, '数据不能为空');
        }

        foreach ($match_data as $key => $value) {
            foreach ($value['list'] as $k => $v) {
                foreach ($v as  $valueKey => $valueData) {
                    $v[$valueKey] = trim($valueData);
                    if (empty($v[$valueKey])) {
                        return $this->jsonAdminResult([],10001, '数据不能为空');
                    }
                }
                $value['list'][$k] = $v;
            }
            $match_data[$key] = $value;
        }

        $time = date('Y-m-d H:i:s');
        sort($tag_id);
        $res = $mMatch->insert([
            'ptag_id' => $ptag_id,
            'tag_id' => json_encode($tag_id),
            'match_play' => $match_play,
            'match_score' => $match_score,
            'match_result' => $match_result,
            'match_half_audience' => $match_half_audience,
            'match_type' => $match_type,
            'match_data' => json_encode($match_data),
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
     * @name 修改比赛
     * @Post("/lv/match/match/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Match $mMatch)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $ptag_id = $params['ptag_id'] ?? 0;
        $tag_id = $params['tag_id'] ?? [];
        $match_play = trim($params['match_play'] ?? '');
        $match_score = trim($params['match_score'] ?? '');
        $match_result = trim($params['match_result'] ?? '');
        $match_half_audience = trim($params['match_half_audience'] ?? '');
        $match_type = trim($params['match_type'] ?? '');
        $match_data = $params['match_data'] ?? [];

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($tag_id)) {
            return $this->jsonAdminResult([],10001, '请选择标签');
        }

        if (empty($match_play)) {
            return $this->jsonAdminResult([],10001, '比赛场次不能为空');
        }

        if (empty($match_score)) {
            return $this->jsonAdminResult([],10001, '比分不能为空');
        }

        if (empty($match_result)) {
            return $this->jsonAdminResult([],10001, '比赛结果不能为空');
        }

        if (empty($match_half_audience)) {
            return $this->jsonAdminResult([],10001, '半全场不能为空');
        }

        if (empty($match_type)) {
            return $this->jsonAdminResult([],10001, '比赛类型不能为空');
        }

        if (empty($match_data)) {
            return $this->jsonAdminResult([],10001, '数据不能为空');
        }

        foreach ($match_data as $key => $value) {
            foreach ($value['list'] as $k => $v) {
                foreach ($v as  $valueKey => $valueData) {
                    $v[$valueKey] = trim($valueData);
                    if (empty($v[$valueKey])) {
                        return $this->jsonAdminResult([],10001, '数据不能为空');
                    }
                }
                $value['list'][$k] = $v;
            }
            $match_data[$key] = $value;
        }

        $time = date('Y-m-d H:i:s');
        sort($tag_id);
        $res = $mMatch->where('id', $id)->update([
            'ptag_id' => $ptag_id,
            'tag_id' => json_encode($tag_id),
            'match_play' => $match_play,
            'match_score' => $match_score,
            'match_result' => $match_result,
            'match_half_audience' => $match_half_audience,
            'match_type' => $match_type,
            'match_data' => json_encode($match_data),
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除比赛
     * @Post("/lv/match/match/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Match $mMatch)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mMatch->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
