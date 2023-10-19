<?php

namespace App\Model\Admin;

use App\Facades\PermissionFacade;
use Illuminate\Support\Facades\DB;

class Permission extends BaseModel
{
    public $table = 'permissions';
    public $db = '';

    public $data = [];
    public $fileList = [];

    /**
     * 权限列表
     * @param array $params
     * @return array
     */
    public function getList($params=[])
    {
        $where = [];
        $where[] = ['is_white', '=', 0];
        $where[] = ['path', 'like', '@%'];

        if ($params['is_show'] > -1) {
            $where[] = ['is_show', '=', $params['is_show']];
        }

        if (!empty($params['id_path']) ) {
            $where[] = [function ($query) use ($params) {
                $tmp = explode('|', $params['id_path']);
                $curId = $tmp[count($tmp) - 1]; // 获取查询的权限id
                $query->where('id_path', 'like', "0|{$params['id_path']}|%")
                    ->orWhere('id_path', '=', "0|{$params['id_path']}")
                    ->orWhere('id', '=', "{$curId}");
            }];
        }

        $total = $this->where($where)->count();
        $items = $this->where($where)->orderBy('id_path', 'asc')->orderBy('id', 'asc')->offset(($params['page'] - 1) * $this->pageSize)->limit($this->pageSize)->get(['*', DB::raw('concat(id_path, "|", id) as idPath')]);

        return $data = [
            'total' => $total,
            'items' => $items
        ];
    }

    /**
     * 获取权限
     * @return array|mixed
     */
    public function getPermissions()
    {
        $where[] = ['is_white', '=', 0];
        $where[] = ['path', '!=', 'Welcome'];
        $permissions = $this->where($where)->orderBy('p_id', 'asc')->orderBy('id', 'asc')->get()->toArray();

        if (empty ($permissions)) {
            return [];
        }

        foreach ($permissions as $permission) {
            $tmp = explode('|', $permission['id_path']);
            array_shift($tmp);
            $permission['id_path'] = implode('|', $tmp);

            if ($permission['p_id'] == 0) {
                $this->data[$permission['id']] = [
                    'id' => $permission['id'],
                    'name' => $permission['name'],
                    'key' => $permission['path'],
                    'id_path' => $permission['id_path'],
                ];
            } else {
                $idPath = explode('|', $permission['id_path']);
                $this->formatResult($permission, $idPath);
            }
        }

        // 按admin.nav配置进行排序
        $navList = config('admin.nav');
        $navList = array_column($navList,'sort','alias');
        $data = [];
        foreach ($this->data as $value) {
            if (isset($navList[$value['name']])) {
                $data[$navList[$value['name']]] = $value;
            }
        }
        $data = $this->sortResult($data);

        return $data;
    }

    /**
     * 去除数组的建
     * @param $data
     * @return mixed
     */
    protected function sortResult($data)
    {
        ksort($data);
        $data = array_values($data);
        foreach ($data as $key => $val) {
            if (!empty($data[$key]['children']) ) {
                $data[$key]['children'] = $this->sortResult($data[$key]['children']);
            }
        }

        return $data;
    }

    /**
     * 格式权限数组
     * @param $permission
     * @param $idPath
     */
    protected function formatResult($permission, $idPath)
    {
        if (empty($idPath)) {
            return [];
        }

        if (count($idPath) > 1) {
            $cur = $idPath[0];
            array_shift($idPath);
            $next = $idPath[0];
            array_shift($idPath);

            if (count($idPath) == 0) {
                $this->data[$cur]['children'][$next]['children'][$permission['id']] = [
                    'id' => $permission['id'],
                    'name' => $permission['name'],
                    'key' => $permission['path'],
                    'id_path' => $permission['id_path'],
                ];
            } else {
                $this->data[$cur]['children'][$next][] = $this->formatResult($permission, $idPath);
            }
        } else {
            $this->data[$permission['p_id']]['children'][$permission['id']] = [
                'id' => $permission['id'],
                'name' => $permission['name'],
                'key' => $permission['path'],
                'id_path' => $permission['id_path'],
            ];
        }
    }

    /**
     * 刷新权限
     * @return int
     * @throws \ReflectionException
     */
    public function refresh()
    {
        $dir = app_path('Http/Controllers/Admin/');
        $this->scanFile($dir);
        $fileList = $this->fileList;

        // 通过注释获取权限信息
        $permissionList = $this->getPermissionByComment($fileList);
        if (empty($permissionList)) {
            return 20001;
        }

        // 刷新一级权限
        $navList = config('admin.nav');
        if (empty($navList)) {
            return 20002;
        }

        foreach ($navList as $key => $val) {
            $this->_refreshMysql(['p_id', 'name', 'path', 'id_path', 'created_at', 'updated_at'], 'path', [
                0 => [
                    'p_id' => 0,
                    'name' => $val['alias'],
                    'path' => PermissionFacade::pathEncode($key),
                    'id_path' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);

            $paths[] = $key;
        }

        $list = $this->whereIn('path', $paths)->get()->toArray();
        if ( empty($list) ) {
            return 20001;
        }

        foreach ($list as $val) {
            $navList[$val['path']]['id'] = $val['id']; // 追加数据库id
        }

        // 更新数据库权限信息
        $nav_delete_list = []; // 需要删除的一级目录
        foreach ($permissionList as $permissions) {
            $tmp = explode('_', $permissions['path']);
            $nav = $tmp['1'];
            if (!array_key_exists($nav, $navList)) {
                $nav_delete_list[] = $nav;
                continue;
            }
            $pId = $navList[$nav]['id'];

            // 刷新二级权限
            $path = $permissions['path'];
            $this->_refreshMysql(['p_id', 'name', 'is_white', 'path', 'id_path', 'created_at', 'updated_at'], 'path', [
                0 => [
                    'p_id' => $pId,
                    'name' => $permissions['name'],
                    'is_white' => $permissions['is_white'],
                    'path' => PermissionFacade::pathEncode($path),
                    'id_path' => sprintf("0|%s", $pId),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);

            // 获取指定类的二级权限id
            $parent = $this->where([
                'path' => $path,
                'p_id' => $pId
            ])->first()->toArray();

            if (!empty($permissions['sub'])) {
                // 刷新三级权限
                foreach ($permissions['sub'] as $sub) {
                    $path = $sub['path'];
                    $subData[] = [
                        'p_id' => $parent['id'],
                        'name' => $sub['name'],
                        'is_white' => $parent['is_white'] ? $parent['is_white'] : $sub['is_white'],
                        'path' => PermissionFacade::pathEncode($path),
                        'id_path' => sprintf("0|%s|%s", $pId, $parent['id']),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $this->_refreshMysql(['p_id', 'name', 'is_white', 'path', 'id_path', 'created_at', 'updated_at'], 'path', $subData);

                // 删除无用的三级权限
                $temp_path = array_column($subData, 'path');
                $this->where('p_id', $parent['id'])->whereNotIn('path', $temp_path)->delete();
            }
        }

        // 删除无用的权限
        $temp_path = array_keys($navList);
        $info_list = $this->where('p_id', 0)->whereNotIn('path', $temp_path)->get();
        $info_list = json_decode(json_encode($info_list), true);
        $nav_delete_list = array_merge($nav_delete_list, array_column($info_list, 'path'));
        $nav_delete_list = array_unique($nav_delete_list);
        foreach ($nav_delete_list as $value) {
            $info = $this->where('path', $value)->first();
            $info = json_decode(json_encode($info), true);
            if (!empty($info)) {
                $this->where('id', $info['id'])->orWhere('p_id', $info['id'])->orWhere('id_path', 'like', "0|{$info['id']}|%")->delete();
            }
        }

        // 设置栏目
        $nav_show_list = config('admin.nav_show_list');
        $this->whereIn('path', $nav_show_list)->update(['is_show' => 1]);

        return 10000;
    }

    /**
     * 通过注释获取权限信息
     * @param array $fileList
     * @return array
     * @throws \ReflectionException
     */
    protected function getPermissionByComment($fileList = [])
    {
        $permissionList = [];
        if (!empty($fileList)) {
            $i = 0;
            foreach ($fileList as $file) {
                $file = str_replace('\\', '/', $file); // 兼容windows路径
                $className = str_replace(
                    ["app", ".php", "/"],
                    ["App", "", "\\"],
                    substr($file, strpos($file, "/app/"), strrpos($file, ".php"))
                );
                $class = new \ReflectionClass($className);

                // 类
                $classComment = $class->getDocComment();
                if (preg_match("/@name .*/i", $classComment, $match)) {
                    $isWhite = preg_match("/@PermissionWhiteList/i", $classComment, $isWhite)? 1: 0;
                    $name = trim(str_replace("@name ", "", $match[0]));
                    $permissionList[$i] = [
                        "name" => $name,
                        "is_white" => $isWhite,
                        "path" => PermissionFacade::pathEncode($className)
                    ];

                    // 方法
                    $methods = $class->getMethods();
                    foreach ($methods as $method) {
                        $comment = $method->getDocComment();
                        if (preg_match("/@name .*/i", $comment, $match) && preg_match("/@(post|get|put|patch|delete|copy|head|options|link|unlink|purge|lock|unlock|propfind|view)\(.*\).*/i", $comment, $matchMethod) ) {
                            $isWhite = preg_match("/@PermissionWhiteList/i", $comment, $isWhite)? 1: 0;
                            $name = trim(str_replace("@name ", "", $match[0]));
                            $method = trim($matchMethod[0]);
                            $permissionList[$i]["sub"][] = [
                                "name" => $name,
                                "is_white" => $isWhite,
                                "path" => $method
                            ];
                            usleep(100);
                        }
                    }
                }
                $i++;
            }
        }

        return $permissionList;
    }

    /**
     * 遍历文件
     * @param string $path
     * @return bool
     */
    public function scanFile($path = ''){
        if ($path == '') {
            return false;
        }

        $file = new \FilesystemIterator($path);
        foreach ($file as $fileinfo) {
            if($fileinfo->isDir()){
                $this->scanFile($path . $fileinfo->getFilename() . '/');
            } else if ($fileinfo->isFile()) {
                $this->fileList[] = $path.$fileinfo->getFilename();
            }
        }
    }

    /**
     * 批量插入或更新
     * @param array $fields 插入字段
     * @param array $data 数据集合
     * @return bool
     */
    private function _refreshMysql($fields = [], $whereFields = '', $data = [])
    {
        $result1 = $result2 = true;
        if (empty($fields) || empty($whereFields) || empty($data) ) {
            return false;
        }

        if (count($data) > 1000) {
            return false;
        }

        foreach ($data as $val) {
            $params[] = $val[$whereFields];
        }

        $result = $this->whereIn($whereFields, $params)->get($whereFields)->toArray();

        // update
        if (!empty($result)) {
            $exists = [];
            foreach ($result as $val) {
                $exists[] = $val['path'];
            }

            $name = '';
            $isWhite = '';
            $pid = '';
            $idPath = '';
            foreach ($data as $key => $item) {
                if (in_array($item['path'], $exists)) {
                    unset($data[$key]);
                    $item['is_white'] = empty($item['is_white']) ? 0 : $item['is_white'];
                    $name .= sprintf("WHEN '%s' THEN '%s'\n", $item['path'], $item['name']);
                    $isWhite .= sprintf("WHEN '%s' THEN '%s'\n", $item['path'], $item['is_white']);
                    $pid .= sprintf("WHEN '%s' THEN '%s'\n", $item['path'], $item['p_id']);
                    $idPath .= sprintf("WHEN '%s' THEN '%s'\n", $item['path'], $item['id_path']);
                }
            }

            $name = trim($name, ',');
            $isWhite = trim($isWhite, ',');
            $pid = trim($pid, ',');
            $idPath = trim($idPath, ',');
            $pathStr = implode("','", $exists);
            $sql = sprintf("UPDATE `%s` SET `name` = (CASE `path` %s END), `is_white` = (CASE `path` %s END), `p_id` = (CASE `path` %s END), `id_path` = (CASE `path` %s END) where `path` in ('%s')",
                $this->table,
                $name,
                $isWhite,
                $pid,
                $idPath,
                $pathStr
            );

            $result1 = DB::statement($sql);
        }

        // insert
        if (!empty($data)) {
            $values = '';
            foreach ($data as $k => $val) {
                $values .= '(';
                foreach ($fields as $field) {
                    $insertValue = addslashes($val[$field]);

                    $values .= "'{$insertValue}',";
                }
                $values = rtrim($values, ',');
                $values .= '),';
            }
            $values = rtrim($values, ',');
            $sql = sprintf("INSERT INTO `%s`(`%s`) VALUES %s",
                $this->table,
                implode("`,`", $fields),
                $values
            );

            $result2 = DB::statement($sql);
        }

        return $result1 && $result2;
    }
}
