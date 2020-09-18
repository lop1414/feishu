<?php


namespace App\Enums;


class ExceptionTypeEnums
{
    const DEFAULT = 'DEFAULT';
    const CUSTOM = 'CUSTOM';

    static public $list = [
        ['id' => self::DEFAULT, 'name' => '默认'],
        ['id' => self::CUSTOM, 'name' => '自定义'],
    ];

}
