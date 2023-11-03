<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Admin\Article;
use App\Model\Admin\Slide;
use Illuminate\Http\Request;

/**
 * 首页
 */
class IndexController extends Controller
{
    use FormatTrait;

    /**
     * 幻灯片和文章
     * @param Request $request
     */
    public function list(Request $request, Slide $mSlide, Article $mArticle)
    {
        $params = $request->all();

        $urlPre = config('filesystems.disks.tmp.url');

        $slide_list = $mSlide->get(['id', 'title', 'image']);
        $slide_list = $this->dbResult($slide_list);
        foreach ($slide_list as $key => $value) {
            $slide_list[$key]['image'] = $urlPre . $value['image'];
        }

        $article_list = $mArticle->get(['id', 'title', 'image']);
        $article_list = $this->dbResult($article_list);
        foreach ($article_list as $key => $value) {
            $article_list[$key]['image'] = $urlPre . $value['image'];
        }

        return $this->jsonAdminResult(['slide_list' => $slide_list, 'article_list' => $article_list]);
    }

    /**
     * 文章详情
     * @param Request $request
     */
    public function detail(Request $request, Article $mArticle)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $info = $mArticle->where('id', $id)->first();
        $info = $this->dbResult($info);

        return $this->jsonAdminResult([
            'data' => $info
        ]);
    }
}
