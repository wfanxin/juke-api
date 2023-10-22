<?php

namespace App\Http\Controllers\Api;

use App\Common\Upload;
use App\Http\Traits\FormatTrait;
use Illuminate\Http\Request;

/**
 * 服务
 */
class ServiceController extends Controller
{
    use FormatTrait;

    /**
     * 上传文件
     * @param Request $request
     */
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        $tmpFile = '';
        if (!empty($file)) {
            $upload = new Upload();
            $tmpFile = $upload->uploadToTmp($file, 'api/' . $request->memId . '/');
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
