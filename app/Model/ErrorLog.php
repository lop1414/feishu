<?php


namespace App\Model;

class ErrorLog extends Base
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'error_logs';

    /**
     * 关联到模型数据表的主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var bool
     * 关闭自动更新时间戳
     */
    public $timestamps= false;

    /**
     * @param $value
     * @return array
     * 属性访问器
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     * 属性修饰器
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }
}
