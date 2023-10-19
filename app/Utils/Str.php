<?php

namespace App\Utils;

/**
 * 处理字符串
 */
class Str
{
    /** 过滤特殊字符
     * @param $str
     * @return array|string|string[]
     */
    public function safeExcelNotDot($str)
    {
        $str = str_replace([
            '=','#','?',',','%','!','@','$','^','&','(',')',';',':',"'",'，','。','`','~','|','[',']','{','}','_','+'
        ], [
            '','','','','','','','','','','','','','','','','','','','','','','','','','',''
        ], $str);

        return $str;
    }

}
