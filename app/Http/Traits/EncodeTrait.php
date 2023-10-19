<?php

namespace App\Http\Traits;

/**
 * 加密
 * Class ResponseTrait
 * @package App\Http\Traits
 */
trait EncodeTrait {
    /**
     * 获取加密密码
     * @param $pwd
     * @param string $salt
     * @return string
     */
    public function _encodePwd($pwd, $salt = '')
    {
        $this->_salt = empty($salt) ? md5(rand(0, time()) . time()): $salt;
        return md5(base64_encode(sprintf("%s%s%s", $pwd, $this->_salt, $pwd)));
    }

    /**
     * 加密显示
     * @param $str
     * @return mixed|string
     */
    public function _encodeName($str)
    {
        // 计算字符串长度，无论汉字还是英文字符全部为1
        $length = mb_strlen($str, 'utf-8');
        // 截取第一部分代码
        $firstStr1 = mb_substr($str, 0, ceil($length/4), 'utf-8');
        // 截取中间部分代码
        $firstStr = mb_substr($str, ceil($length/4), floor($length/2), 'utf-8');
        // （方法一）截取剩余字符串
        $firstStr2 = mb_substr($str, ceil($length/4) + floor($length/2), floor(($length+1)/2 - 1),'utf-8');
        return $firstStr1 . str_repeat("*", mb_strlen($firstStr, 'utf-8')) . $firstStr2;
    }
}
