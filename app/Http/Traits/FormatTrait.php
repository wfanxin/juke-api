<?php

namespace App\Http\Traits;


/**
 * 格式化
 * Class FormatTrait
 * @package App\Http\Traits
 */
trait FormatTrait {
    /**
     * 获取两个日期差的天数
     * @param $day1
     * @param $day2
     * @return float
     */
    public function diffDay($day1, $day2)
    {
        $d1 = strtotime(date('Y-m-d', strtotime($day1)));
        $d2 = strtotime(date('Y-m-d', strtotime($day2)));

        $diffDay = ($d1 - $d2) / 86400;
        return $diffDay;
    }

    public function price($price=0)
    {
        if (! empty($price)) {
            $price = explode('.', floatval($price) * 100)[0] / 100;
        } else {
            $price = "0.00";
        }
        $int = explode(".", $price)[0];
        $decimal = empty(explode(".", $price)[1])? "00": str_pad(explode(".", $price)[1], 2, "0", STR_PAD_RIGHT);

        $format = $int . "." . $decimal;
        return $format;
    }

    /**
     * 文件大小格式化
     * @param $file
     * @param int $type  1=KB  2=MB 3=GB
     * @return string
     */
    public function fileSize($file, int $type = 2 , $digit = 2)
    {
        $size = filesize($file);
        if ($type == 1) {
            $size = $size / 1024;
        } else if ($type == 2 ){
            $size = $size / (1024*1024);
        } else if ($type == 3 ){
            $size = $size / (1024*1024*1024);
        }
        return round($size, $digit);
    }

    /**
     * @param $str
     * @return string
     */
    public function unicode_encode($str) {
        $strArr = preg_split('/(?<!^)(?!$)/u', $str);//拆分字符串为数组(含中文字符)
        $resUnicode = '';
        foreach ($strArr as $str)
        {
            $bin_str = '';
            $arr = is_array($str) ? $str : str_split($str);//获取字符内部数组表示,此时$arr应类似array(228, 189, 160)
            foreach ($arr as $value)
            {
                $bin_str .= decbin(ord($value));//转成数字再转成二进制字符串,$bin_str应类似111001001011110110100000,如果是汉字"你"
            }
            $bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/', '$1$2$3', $bin_str);//正则截取, $bin_str应类似0100111101100000,如果是汉字"你"
            $unicode = dechex(bindec($bin_str));//返回unicode十六进制
            $_sup = '';
            for ($i = 0; $i < 4 - strlen($unicode); $i++)
            {
                $_sup .= '0';//补位高字节 0
            }
            $str =  '_u' . $_sup . $unicode; //加上 \u  返回
            $resUnicode .= $str;
        }
        return $resUnicode;
    }

    /**
     * db 结果强制格式化
     * @param $data
     * @return mixed
     */
    public function dbResult($data)
    {
        return json_decode(json_encode($data), true);
    }

    /**
     * 格式化数组自然下标
     */
    public function arrIndexByNat($arrTmp=[])
    {
        if (empty($arrTmp)) {
            return $arrTmp;
        }

        $arr = [];
        foreach ($arrTmp as $k => $val) {
//            var_dump($k);
            $arr[] = $val;
        }

        return $arr;
    }

    /**
     * 扫描枪输入运单号格式化
     */
    public function deliveryByScan($deliveryNo)
    {
        $deliveryNo = str_replace(' ', '', $deliveryNo);
        if (preg_match('/WAYBILL[0-9]{1,}/', $deliveryNo, $match)) { // 敦煌DHL莆田邮通仓
            $deliveryNo = str_replace('WAYBILL', '', $match[0]);
        } else if (preg_match('/\d{1,}\s+DHLink/', $deliveryNo)) { // 敦煌仓条形码
            $deliveryNo = str_replace('DHLink', '', trim($deliveryNo));
        }

        return $deliveryNo;
    }

    /**
     * 获取中英文分类
     */
    public function getCustom($mineCustomName)
    {
        $en = substr($mineCustomName,strrpos($mineCustomName, '(')+1);
        $zh = substr($mineCustomName,0, strrpos($mineCustomName, '('));

        return [
            'en' => str_replace(['(',')'], [], $en),
            'zh' => $zh,
        ];
    }

    /**
     * excel日期转换
     */
    public function excelDateToDate($excelTime) {
        if (empty($excelTime)) {
            return '';
        }

        $excelTime = ($excelTime - 25569) * 86400 - 8 * 3600;
        return date('Y-m-d H:i:s', $excelTime);
    }
}
