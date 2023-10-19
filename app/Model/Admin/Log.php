<?php

namespace App\Model\Admin;

use App\Facades\PermissionFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Log extends Model
{
    public $table = 'logs';

    /**
     * @param $params
     * @return array
     */
    public function getList($params)
    {
        $logJoinUsers = DB::table('logs')
            ->select('logs.*', 'users.user_name', 'users.id', 'users.name')
            ->leftJoin('users', 'logs.op_uid', '=', 'users.id');

        if (!empty($params['user_name'])) {
            $logJoinUsers->where('users.user_name', 'like', "%{$params['user_name']}%");
        }

        if (!empty($params['select_time'][0]) && !empty($params['select_time'][1])) {
            $logJoinUsers->where('logs.created_at', '>=', $params['select_time'][0])
                ->where('logs.created_at', '<=', $params['select_time'][1]);
        }

        $name = [];
        if (!empty($params['permission'])) {
            $permissions = explode('|', $params['permission']);
            $permissionId = end($permissions);
            $idPath = sprintf('0|%s', $params['permission']);
            $permissionsList = DB::table('permissions')
                ->where('id_path', 'like', $idPath . '|%')
                ->orWhere('id_path', $idPath)
                ->orWhere('id', $permissionId)
                ->get('name');
            if (! empty($permissionsList) ) {
                foreach ($permissionsList as $val) {
                    $name[] = $val->name;
                }
            }
        }

        if (!empty($name)) {
            $logJoinUsers->whereIn('logs.name', $name);
        }

        $list = $logJoinUsers->orderBy('logs.created_at', 'desc')
            ->paginate(15, ['*'], 'page', $params['page']);

        $data = [];
        if (!empty($list->items())) {
            foreach ($list->items() as $val) {
                $val->request = unserialize($val->request);
                $val->response = unserialize($val->response);
                $data[] = $val;
            }
        }

        return [$list, $data];
    }

    /**
     * @param Request $request
     * @param $userId
     * @return int|mixed
     *
     */
    public function add(Request $request, $response = []) {
        // 格式成数据库path字段格式
        $xPermission = PermissionFacade::getRequestPath();

        $op = $request->getMethod();
        if (in_array($op, ['POST', 'PATCH', 'PUT', 'DELETE']) && !empty($request->userId)) {
            $permission = DB::table('permissions')->where('path', $xPermission)->first('name');
            if (!empty($permission)) {
                $requestMsg = serialize([
                    'name' => $permission->name,
                    'url' => $xPermission,
                    'param' => $request->all()
                ]);

                // 添加
                return $this->insert([
                    'op_uid' => $request->userId,
                    'ip' => $request->getClientIp(),
                    'name' => $permission->name,
                    'request' => $requestMsg,
                    'response' => serialize($response),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return 0;
    }
}
