<?php
namespace App\Utils;

/**
 * Class Permission
 * @package App\Utils
 */
class Permission
{
    /**
     * 格式化权限路径
     * @param string $path
     * @return mixed
     */
    public function pathEncode($path = '')
    {
        return str_replace([
            '\\App\\Http\\Controllers\\','("/', '("', '/',  '")' , '\\'
        ],[
            '',':', ':', '_', '', '_'
        ], $path);
    }

    /**
     * 获取权限路径
     * @return mixed|string
     */
    public function getRequestPath()
    {
        $tmp = explode('?', $_SERVER['REQUEST_URI']);
        $api = $tmp[0];
        $method = ucfirst(strtolower($_SERVER['REQUEST_METHOD']));
        $apiArr = explode('/', $api);
        $last = end($apiArr);
        if (is_numeric($last)) {
            array_pop($apiArr);
            $api = implode('/', $apiArr);
            $xPermission = '@'.$method.'("'.$api.'/{?id}")';
        } else {
            $xPermission = sprintf('@%s("%s")', $method, $api);
        }
        $xPermission = $this->pathEncode($xPermission);

        return $xPermission;
    }
}
