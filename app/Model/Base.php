<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Base extends Model
{

    protected $tableColumns;
    protected $updateIgnoreFields = [];
    protected $packageDbTables = [];

    /**
     * Base constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * @param $filtering
     * @return mixed
     * 公共过滤
     */
    public function scopeFiltering($query, $filtering){
        // 映射
        $operatorMap = [
            'EQUALS' => '=',
            'LESS_EQUALS' => '<=',
            'LESS' => '<',
            'GREATER_EQUALS' => '>=',
            'GREATER' => '>',
            'LIKE' => 'LIKE',
            'IN' => 'IN'
        ];

        $where = ['1'];
        // 绑定参数
        $parameters = [];
        foreach($filtering as $f){
            // 占位符
            $placeholder = '?';

            $f['operator'] = strtoupper($f['operator']);
            // 默认
            $operator = $operatorMap[$f['operator']] ?? '=';

            // 适配
            switch($operator){
                case 'LIKE':
                    // 绑定参数
                    $parameters[] = "%{$f['value']}%";
                    break;
                case 'IN':
                    // 占位符
                    $placeholder = '';
                    if(!empty($f['value'])){
                        $comma = '';
                        foreach($f['value'] as $v){
                            $placeholder .= $comma . '?';
                            $comma = ',';
                            // 绑定参数
                            $parameters[] = $v;
                        }
                        $placeholder = "($placeholder)";
                    }
                    break;
                default:
                    // 绑定参数
                    $parameters[] = $f['value'];
                    break;
            }

            // 占位符不为空, 拼接条件
            if(!empty($placeholder)){
                $where[] = "`{$f['field']}` {$operator} {$placeholder}";
            }
        }

        $whereRaw = implode(" AND ", $where);

        return $query->whereRaw($whereRaw, $parameters);
    }

    /**
     * @param $query
     * @param int $page
     * @param int $pageSize
     * @return array
     * 分页数据
     */
    public function scopeListPage($query, $page = 1, $pageSize = 10){
        // 总数
        $total = $query->count();

        $page = max($page, 1);
        $offset = ($page - 1) * $pageSize;

        // 列表
        $list = $query->skip($offset)->take($pageSize)->get();

        return [
            'list' => $list,
            'page_info' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_page' => ceil($total / $pageSize),
            ],
        ];
    }

    /**
     * @return mixed
     * 开启数据
     */
    public function scopeEnable($query){
        return $query->where('status', StatusEnums::ENABLE);
    }

    /**
     * @param $field
     * @param $value
     * @return bool
     * 按字段检索是否重复
     */
    public function exist($field, $value){
        return !!$this->where($field, $value)->first();
    }

    /**
     * @param $field
     * @param $value
     * @param $primaryValue
     * @return bool
     * 按字段检索是否重复（去除自身）
     */
    public function existWithoutSelf($field, $value, $primaryValue){
        $where = [
            [$field, '=', $value],
            [$this->primaryKey, '<>', $primaryValue],
        ];
        return !!$this->where($where)->first();
    }

    /**
     * @param null $database
     * @param null $table
     * @return array
     * 获取表字段 （排除主键）
     */
    public function getTableColumns($database = null, $table = null){
        // 数据库名
        $datebase = $database ?? config('database.connections.mysql.database');
        // 表名
        $table = $table ?? $this->getTable();

        // 兼容
        $tmp = explode(".", $table);
        if(count($tmp) == 2){
            $datebase = $tmp[0];
            $table = $tmp[1];
        }

        if(empty($this->tableColumns[$table])){
            $sql = sprintf("SELECT column_name FROM information_schema.columns WHERE table_schema='%s' AND table_name='%s' AND column_name != '{$this->primaryKey}' ", $datebase, $table);

            $data = DB::select($sql);

            $this->tableColumns[$table] = array_column($data, 'column_name');
        }

        return $this->tableColumns[$table];
    }

    /**
     * 分块更新数据
     * @param $data
     * @param int $size
     * @return array
     */
    public function chunkInsertOrUpdate($data, $size = 1000){
        $chunk_data = array_chunk($data, $size);

        $ret = [];
        foreach ($chunk_data as $item){
            $ret[] = $this->batchInsertOrUpdate($item);
        }

        return $ret;
    }

    /**
     * @param $data
     * @param string $table
     * @param array $columns
     * @return bool|int
     * 批量插入或更新表中数据
     */
    public function batchInsertOrUpdate($data, $table = '', $columns = []){
        return $this->batchInsert($data, $table, $columns, true);
    }

    /**
     * @param $data
     * @param string $table
     * @param array $columns
     * @param bool $duplicate
     * @return bool|int
     * 批量插入
     */
    public function batchInsert($data, $table = '', $columns = [], $duplicate = false){
        if(empty($data)){//如果传入数据为空 则直接返回
            return false;
        }

        empty($table) && $table = $this->getTable();  //如果未传入table则通过对象获得
        empty($columns) && $columns = $this->getTableColumns();  //如果未传入table则通过对象获得

        // 字段名
        $fieldsStr = implode("`,`", $columns);

        // 值
        $values = $parameters = [];
        foreach($data as $k => $v){
            $comma = '';
            $placeholder = '';
            foreach($columns as $column){
                $placeholder .= $comma .'?';
                $comma = ',';
                // 绑定参数
                $parameters[] = $v[$column];
            }
            $values[] = $placeholder;
        }
        $valuesStr = implode("),(", $values);

        // sql
        $sql = "INSERT INTO {$table} (`{$fieldsStr}`) VALUES ({$valuesStr})";

        // 覆盖
        if($duplicate){
            // 更新字段
            $updateFields = array_diff($columns, $this->updateIgnoreFields);
            $duplicates = [];
            foreach($updateFields as $f){
                $duplicates[] = " `{$f}` = VALUES(`{$f}`)";
            }

            $duplicatesStr = implode(",", $duplicates);
            $sql .= " ON DUPLICATE KEY UPDATE {$duplicatesStr}";
        }

        // 执行
        $ret = DB::update(DB::raw($sql), $parameters);

        return $ret;
    }

    /**
     * @param $model
     * @return mixed
     */
    public function extendsField($item){
        foreach($item->extends as $k => $v){
            $item->$k = $v;
        }
        unset($item->extends);
        return $item;
    }
}
