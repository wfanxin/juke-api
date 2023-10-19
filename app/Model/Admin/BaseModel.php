<?php
namespace App\Model\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    public $pageSize = 15;

    /**
     * 批量插入或更新
     * @param array $fields 插入字段
     * @param array $data 数据集合
     * @return bool
     */
    public function InsertOnDuiplicate($fields = [], $data = [])
    {
        if (empty($fields) || empty($data)) {
            return false;
        }

        $sqlTpl = "INSERT INTO `{$this->table}`(`%s`) VALUES %s ON DUPLICATE KEY UPDATE %s";
        $values = '';
        foreach ($data as $k => $val) {
            $values .= '(';
            foreach ($fields as $field) {
                $values .= "'{$val[$field]}',";
            }
            $values = rtrim($values, ',');
            $values .= '),';
        }
        $values = rtrim($values, ',');

        $updates = '';
        foreach ($fields as $field) {
            $updates .= "`{$field}`=VALUES (`{$field}`),";
        }
        $updates = rtrim($updates, ',');

        $sql = sprintf($sqlTpl,
            implode("`,`", $fields),
            $values,
            $updates);

        return DB::statement($sql);
    }
}
