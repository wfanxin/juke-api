<?php

namespace App\Http\Controllers\Admin;

use App\Common\Upload;
use App\Http\Traits\FormatTrait;
use Illuminate\Http\Request;

/**
 * @name 服务
 * Class ServiceController
 * @package App\Http\Controllers\Admin
 *
 * @PermissionWhiteList
 */
class ServiceController extends Controller
{
    use FormatTrait;

    /**
     * @name 上传文件
     * @Get("/lv/service/uploadFile")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        $tmpFile = '';
        if (!empty($file)) {
            $upload = new Upload();
            $tmpFile = $upload->uploadToTmp($file, 'admin/' . $request->userId . '/');
        }

        if ($tmpFile) {
            return $this->jsonAdminResult([
                'file' => config('filesystems.disks.tmp.url') . $tmpFile
            ]);
        } else {
            return $this->jsonAdminResult([],10001,'上传失败');
        }
    }
}
