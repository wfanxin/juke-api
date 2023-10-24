<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Article;
use Illuminate\Http\Request;

/**
 * @name 平台说明管理
 * Class ArticleController
 * @package App\Http\Controllers\Admin\Mobile
 *
 * @Resource("articles")
 */
class ArticleController extends Controller
{
    use FormatTrait;

    /**
     * @name 平台说明列表
     * @Get("/lv/mobile/article/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Article $mArticle)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        // 标题
        if (!empty($params['title'])){
            $where[] = ['title', 'like', '%' . $params['title'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mArticle->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $urlPre = config('filesystems.disks.tmp.url');
            foreach ($data->items() as $k => $v){
                $data->items()[$k]['image'] = $urlPre . $v->image;
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }

    /**
     * @name 添加平台说明
     * @Post("/lv/mobile/article/add")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function add(Request $request, Article $mArticle)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $title = $params['title'] ?? '';
        $image = $params['image'] ?? '';
        $content = $params['content'] ?? '';

        if (empty($title)) {
            return $this->jsonAdminResult([],10001, '标题不能为空');
        }

        if (empty($image)) {
            return $this->jsonAdminResult([],10001, '图片不能为空');
        }

        if (empty($content)) {
            return $this->jsonAdminResult([],10001, '内容不能为空');
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $image = str_replace($urlPre, '', $image);

        $time = date('Y-m-d H:i:s');
        $res = $mArticle->insert([
            'title' => $title,
            'image' => $image,
            'content' => $content,
            'created_at' => $time,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 修改平台说明
     * @Post("/lv/mobile/article/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Article $mArticle)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $title = $params['title'] ?? '';
        $image = $params['image'] ?? '';
        $content = $params['content'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($title)) {
            return $this->jsonAdminResult([],10001, '标题不能为空');
        }

        if (empty($content)){
            return $this->jsonAdminResult([],10001, '内容不能为空');
        }

        $urlPre = config('filesystems.disks.tmp.url');
        $image = str_replace($urlPre, '', $image);

        $time = date('Y-m-d H:i:s');
        $res = $mArticle->where('id', $id)->update([
            'id' => $id,
            'title' => $title,
            'image' => $image,
            'content' => $content,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除平台说明
     * @Post("/lv/mobile/article/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Article $mArticle)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mArticle->where('id', $id)->delete();

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
