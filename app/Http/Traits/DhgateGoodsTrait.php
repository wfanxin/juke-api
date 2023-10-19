<?php

namespace App\Http\Traits;


/**
 * 敦煌产品处理
 * Class FormatTrait
 * @package App\Http\Traits
 */
trait DhgateGoodsTrait {
    /**
     * 修改标题防止重复
     */
    protected function titleSolt($name){
        $str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $titleFix = '';
        for($i=0; $i<4; $i++){
            if($i == 1){
                $titleFix.=rand(0,9);
            }else{
                $k = rand(0,61);
                $titleFix .= $str[$k];
            }
        }
        $titleFix .= "#";

        if (preg_match("/#$/", $name)){
            $title = preg_replace("/.{5}#$/", "", $name);
            $returnTitle = $title . " " . $titleFix;
        } else {
//            if ( strlen($name) < 135){ // 以前包含各种字符计算长度，现在只计算字符长度
            if ( $this->countStrLength($name) < 136){
                $returnTitle = $name . " " . $titleFix;
            } else {
//                $title = substr($name, 0, 134); // 以前包含各种字符计算长度，现在只计算字符长度
                $title = $this->countStrLength($name, 0, 135);
                $returnTitle = $title . " " . $titleFix;
            }
        }
        return $returnTitle;
    }

    /**
     * 计算字符串长度，和截取非空白字符串长度
     */
    protected function countStrLength($str, $isCount = 1, $getLength = 135){
        if ($isCount) {
            $str = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ", "", $str));
            $return = strlen($str);
        } else {
            $strArr = explode(" ", $str);
            $addLength = 0;
            $returnStr = [];
            foreach ($strArr as $v) {
                if (empty($v)) {
                    continue;
                }
                $nowLength = strlen(preg_replace("/[^A-Za-z0-9]/", "", $v));
                $addLength += $nowLength;
                if ($addLength >= $getLength) {
                    if ($addLength == $getLength) {
                        $returnStr[] = $v;
                        break;
                    } else {
                        if ($nowLength - ($addLength-$getLength) > 0) {
                            $v = substr($v, 0, $nowLength - ($addLength-$getLength));
                            $returnStr[] = $v;
                        }
                        break;
                    }
                }
                $returnStr[] = $v;
            }
            if ($returnStr) {
                $return = join(" ", $returnStr);
            }
        }
        return $return;
    }

    /**
     * 编辑主图
     */
    public function imageEdit($src, $newPathName = '', $legnth = 15){
        try{
            $returnCode = 0;
            $errorMore = '';
            $fileName = $src;
            if (empty($fileName)) {
                throw new \Exception( "图片不能为空".$fileName, 1);
            }
            if (empty($newPathName)) {
                $strReplace = basename($fileName);
                $saveFileName = preg_replace("/(.*)\./", "$1_point.",$strReplace);
                $newPathName = str_replace($strReplace, $saveFileName, $fileName);
            }

//        $this->ImageAddBoard($fileName);
            // 打开图片
            $imagearray=getimagesize($fileName);
            switch($imagearray[2]) {
                case 1://gif
                    $imgtypeTrait = 1;
                    $img = imagecreatefromgif($fileName);
                    break;
                case 2://jpeg
                    $imgtypeTrait = 2;
                    $img = imagecreatefromjpeg($fileName);
                    break;
                case 3://png
                    $imgtypeTrait = 3;
                    $img = imagecreatefrompng($fileName);
                    break;
                default:
                    $img = '';
            }

            if (!is_resource($img)) {
                throw new \Exception( "不是图片资源".json_encode($img), 1);
            }
            $maxX = imagesx($img) - 1;
            $maxY = imagesy($img) - 1;

            for ($yNum = 0; $yNum <= $maxY; $yNum++) {
                for ($ii = 0; $ii < $legnth; $ii++) {
                    $xNum = rand(0, $maxX);
                    $rgb = imagecolorat($img, $xNum, $yNum);
                    $subNumR = rand(1, 20) - 10;
                    $r = ($rgb >> 16) & 0xFF;
                    if ($r+$subNumR > 255) {
                        $R = 255;
                    } else if ($r+$subNumR < 0) {
                        $R = 0;
                    } else {
                        $R = $r+$subNumR;
                    }
                    $subNumG = rand(1, 20) - 10;
                    $g = ($rgb >> 8) & 0xFF;
                    if ($g+$subNumG > 255) {
                        $G = 255;
                    } else if ($g+$subNumG < 0) {
                        $G = 0;
                    } else {
                        $G = $g+$subNumG;
                    }
                    $subNumB = rand(1, 20) - 10;
                    $b = $rgb & 0xFF;
                    if ($b+$subNumB > 255) {
                        $B = 255;
                    } else if ($b+$subNumB < 0) {
                        $B = 0;
                    } else {
                        $B = $b+$subNumB;
                    }
                    $col = imageColorAllocate($img, $R, $G, $B);
                    imagesetpixel($img, $xNum, $yNum, $col);
                }
            }

            switch($imgtypeTrait){
                case 1:
                    imagegif($img, $newPathName);
                    break;
                case 2:
                    imagejpeg($img, $newPathName, 100);
                    break;
                case 3:
                    imagepng($img, $newPathName);
                    break;
                default:
                    throw new \Exception( "图片资源错误".$fileName, 1);
            }
        }catch (\Exception $e) {
            $returnCode = 99;
            if ($e->getCode() != 0) {
                $returnCode = $e->getCode();
            }
            $newPathName = $e->getMessage();
            $errorMore = "图片编辑异常：文件:{$e->getFile()};行号：{$e->getLine()}；信息：{$e->getMessage()}";
        }
        return [
            'code' => $returnCode,
            'file' => $newPathName,
            'messageMore' => $errorMore,
        ];
    }

    /**
     * 图片添加边框
     */
    private function ImageAddBoard($fileName, $px = 2){
        $aPathInfo = pathinfo ( $fileName );
        // 文件名称
        $sFileName = $aPathInfo ['filename'];
        // 图片扩展名
        $sExtension = $aPathInfo ['extension'];
        // 获取原图大小
        list($img_w, $img_h) = getimagesize ( $fileName );

        // 读取图片
        $type = exif_imagetype($fileName);
        $support_type = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
        if (!in_array($type, $support_type, true))
        {
            return false;
        }

        switch($type)
        {
            case IMAGETYPE_JPEG :
                $src_img = imagecreatefromjpeg($fileName);
                break;
            case IMAGETYPE_PNG :
                $src_img = imagecreatefrompng($fileName);
                break;
            case IMAGETYPE_GIF :
                $src_img = imagecreatefromgif($fileName);
                break;
            default :
                return false;
        }

        // 282*282的黑色背景图片
        $im = @imagecreatetruecolor ( $img_w, $img_h ) or die ( "Cannot Initialize new GD image stream" );

        // 为真彩色画布创建背景，再设置为透明
        $r = rand(0, 255);
        $g = rand(0, 255);
        $b = rand(0, 255);
        $color = imagecolorallocate ( $im, $r, $g, $b);
        imagefill ( $im, 0, 0, $color );
        imagecopyresampled($im, $src_img, 1, 1, 1, 1, $img_w -2, $img_h -2, $img_w -2, $img_h -2);
        //imageColorTransparent ( $im, $color );

        // 把品牌LOGO图片放到黑色背景图片上。边框是1px
//        imagecopy ( $im, $resource, $px / 2, $px / 2, 0, 0, $size [0], $size [1] );


        switch($type)
        {
            case IMAGETYPE_JPEG :
                $src_img = imagejpeg($im, $fileName, 70);
                break;
            case IMAGETYPE_PNG :
                $src_img = imagepng($im, $fileName);
                break;
            case IMAGETYPE_GIF :
                $src_img = imagegif($im, $fileName);
                break;
            default :
                return false;
        }

        imagedestroy ( $im );
    }

    /**
     * 等比例缩放函数（以保存新图片的方式实现）
     * @param string $picName 被缩放的处理图片源
     * @param string $savePath 保存路径
     * @param int $minx 放大后图片的最小宽度
     * @param int $miny 放大后图片的最小高度
     * @param string $pre 缩放后图片的前缀名
     * @return $string 返回后的图片名称（） 如a.jpg->s.jpg
     *
     **/
    public function enlargeImg($picName, $savePath = '', $minx = 600, $miny = 600)
    {
        try{
            $pre = '';
            $info = getimageSize($picName);//获取图片的基本信息
            $w = $info[0];//获取宽度
            $h = $info[1];//获取高度

            if($w>=$minx&&$h>=$miny){
                return $picName;
            }

            //获取图片的类型并为此创建对应图片资源
            switch ($info[2]) {
                case 1://gif
                    $im = imagecreatefromgif($picName);
                    break;
                case 2://jpg
                    $im = imagecreatefromjpeg($picName);
                    break;
                case 3://png
                    $im = imagecreatefrompng($picName);
                    break;
                default:
                    return '';
            }
            //计算缩放比例
            if (($minx / $w) > ($miny / $h)) {
                $b = $minx / $w;
            } else {
                $b = $miny / $h;
            }
            //计算出缩放后的尺寸
            $nw = floor($w * $b);
            $nh = floor($h * $b);
            //创建一个新的图像源（目标图像）
            $nim = imagecreatetruecolor($nw, $nh);

            //透明背景变黑处理
            //2.上色
            $color=imagecolorallocate($nim,255,255,255);
            //3.设置透明
            imagecolortransparent($nim,$color);
            imagefill($nim,0,0,$color);

            //执行等比缩放
            imagecopyresampled($nim, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
            //输出图像（根据源图像的类型，输出为对应的类型）
            $picInfo = pathinfo($picName);//解析源图像的名字和路径信息
            if (empty($savePath)) {
                $savePath = str_replace('/'.$picInfo["basename"], '', $picName);
            }
            $savePath = $savePath. "/".$pre . $picInfo["basename"];
            switch ($info[2]) {
                case 1:
                    imagegif($nim, $savePath);
                    break;
                case 2:
                    imagejpeg($nim, $savePath, 85);
                    break;
                case 3:
                    imagepng($nim, $savePath);
                    // imagejpeg($nim, $savePath, 85);
                    break;

            }
            //释放图片资源
            imagedestroy($im);
            imagedestroy($nim);
            //返回结果
            return $savePath;
        } catch(\Exception $e){
            return '';
        }
    }
}