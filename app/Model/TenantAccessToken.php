<?php


namespace App\Model;

class TenantAccessToken extends Base
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'tenant_access_tokens';

    /**
     * 关联到模型数据表的主键
     *
     * @var string
     */
    protected $primaryKey = 'id';
}
