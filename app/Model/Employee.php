<?php


namespace App\Model;

class Employee extends Base
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'employees';

    /**
     * 关联到模型数据表的主键
     *
     * @var string
     */
    protected $primaryKey = 'id';
}
