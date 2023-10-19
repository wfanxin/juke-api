<?php

namespace App\Http\Traits;

/**
 * 图片处理
 * Class ResponseTrait
 * @package App\Http\Traits
 */
trait ImageTrait {
    /**
     * 图片转base64
     * @param $image_file
     * @return string
     */
    public function img2base64($image_file) {
        $image_info = getimagesize($image_file);
        $image_data = file_get_contents($image_file);
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return str_replace("\r\n", '', $base64_image);
    }

    /**
     * base64 转 图片
     * @param string $content
     * @return bool|string
     */
    public function base642img($content='')
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)){
            return base64_decode(str_replace($result[1], '', $content));
        }else{
            return false;
        }
    }
}