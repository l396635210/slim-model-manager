<?php
/**
 * Created by PhpStorm.
 * User: 39663
 * Date: 2017/11/22
 * Time: 10:43
 */

namespace Liz\ModelManager\Helper;


class ModelHelper
{

    /**
     * @var array
     */
    protected static $mapping = [
        'int'       => \PDO::PARAM_INT,
        'smallint'  => \PDO::PARAM_INT,
        'bigint'    => \PDO::PARAM_INT,
        'char'      => \PDO::PARAM_STR,
        'varchar'   => \PDO::PARAM_STR,
        'text'      => \PDO::PARAM_STR,
        'tinyint'   => \PDO::PARAM_BOOL,
        'date'      => \PDO::PARAM_STR,
        'time'      => \PDO::PARAM_STR,
        'datetime'  => \PDO::PARAM_STR,
    ];

    /**
     * @param $fieldInfo
     * @return mixed PDO::PARAM
     */
    public function fetchPdoTypeByFieldInfo($fieldInfo){
        $typeInfo = explode(' ', $fieldInfo)[0];
        $type = explode("(", $typeInfo)[0];
        return self::$mapping[strtolower($type)];
    }

}