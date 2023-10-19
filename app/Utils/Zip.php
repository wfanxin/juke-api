<?php

namespace App\Utils;

class Zip
{

    /**
     * 打包压缩文件及文件夹
     *
     * @DateTime 2020-07-10 13:20:06
     * @param string $dir 保存的地址
     * @param array $files 压缩的目录
     * @param string $zipName 压缩包名称
     * @param boolean $wen
     * @param boolean $isDown
     * @return string
     */
    public function zip_file($dir ='', $files = [], $zipName = '', $wen = true,$isDown = false){
//        $dir = 'zip/';
        if (!is_dir( $dir )) {
            mkdir(  $dir,0777);
        }
        $zip_file_path = $dir.'/';

        // 文件名为空则生成文件名
        if (empty($zipName)) {
            $zipName = $zip_file_path.date('YmdHis') . '.zip';
        }else{
            $zipName = $zip_file_path.$zipName.'.zip';
        }
//        var_dump($zipName);exit();
        // 实例化类,使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
        $zip = new \ZipArchive;

        /*
        * 通过ZipArchive的对象处理zip文件
        * $zip->open这个方法如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        * $zip->open这个方法第一个参数表示处理的zip文件名。
        * 这里重点说下第二个参数，它表示处理模式
        * ZipArchive::OVERWRITE 总是以一个新的压缩包开始，此模式下如果已经存在则会被覆盖。
        * ZipArchive::OVERWRITE 不会新建，只有当前存在这个压缩包的时候，它才有效
        * */
        if ($zip->open($zipName, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE) !== true) {
            exit('无法打开文件，或者文件创建失败');
        }

        // 文件夹打包处理
        if (is_string($files)) {
//            var_dump(1);exit();
            // 文件夹整体打包
            $this->addFileToZip($files, $zip, $files);
        } else {
//            var_dump(2);exit();
            // 文件打包
            foreach ($files as $val) {
                if(file_exists(app()->getRootPath().'public'.$val['att_dir'])){
                    if($wen){
                        //根据存储的文件夹打包分层
                        $zip->addFile(app()->getRootPath().'public'.$val['att_dir'], iconv('UTF-8','gbk',$val['img_dir'].'/'.$val['name']));
                    }else{
                        //不分层
                        $zip->addFile(app()->getRootPath().'public'.$val['att_dir'], iconv('UTF-8','gbk',$val['name']));
                    }
                }
            }
        }
        // 关闭
        $zip->close();

        // 验证文件是否存在
        if (!file_exists($zipName)) {
            exit("文件不存在");
        }

        if ($isDown) {
            // ob_clean();
            // 下载压缩包
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename=' . basename($zipName)); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: ' . filesize($zipName)); //告诉浏览器，文件大小
            @readfile($zipName);//ob_end_clean();
        } else {
            // 直接返回压缩包地址
            return $zipName;
        }

    }

    /**
     * 添加文件至压缩包
     *
     * @DateTime 2020-07-10 13:20:26
     * @param [type] $path
     * @param [type] $zip
     * @return void
     */
    public function addFileToZip($path,$zip, $filePath)
    {
        // 打开文件夹
        $handler = opendir($path);
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                //为了解决目录中的内容名字是中文的问题
                if(file_exists(iconv('UTF-8','GBK',$filename))) {
                    // 编码转换
                    $filename = iconv('gbk', 'utf-8', $filename);
                }

                if (is_dir($path . "/" . $filename)) {
                    //当是空文件夹时只创建空的文件夹
//                    $zip->addEmptyDir($path . "/" . $filename);

                    // 如果读取的某个对象是文件夹，则递归
                    $this->addFileToZip($path . "/" . $filename, $zip, $filePath);
                } else {
                    // 将文件加入zip对象
                    $file_path = $path . "/" . $filename;

                    $relativePath = '/' . substr($file_path, strlen($filePath) + 1);
                    //按照相应文件夹结构去生成文件夹和里面的内容
                    $zip->addFile($file_path, $relativePath);
                }
            }
        }
        // 关闭文件夹
        @closedir($path);
    }

    /**
     * 压缩文件解压
     *
     * @DateTime 2020-07-10 13:23:11
     * @param [type] $file
     * @param [type] $dirname
     * @return void
     */
    public  function unzip_file($file, $dirname)
    {
        if (!file_exists($file)) {
            return false;
        }
        // zip实例化对象
        $zipArc = new \ZipArchive();
        // 打开文件
        if (!$zipArc->open($file)) {
            return false;
        }
        // 解压文件
        if (!$zipArc->extractTo($dirname)) {
            // 关闭
            $zipArc->close();
            return false;
        }
        return $zipArc->close();
    }
}

//$server=new ZipServer();
//$sourceDir = 'static';
//$outZipPath = 'zip\test.zip';
//$server->zip($sourceDir, $outZipPath);
//if(file_exists($outZipPath)){
//    echo 'success';
//}else{
//    echo 'fail';
//}


