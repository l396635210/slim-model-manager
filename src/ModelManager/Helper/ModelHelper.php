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
        'mediumint' => \PDO::PARAM_INT,
        'bigint'    => \PDO::PARAM_INT,
        'decimal'   => \PDO::PARAM_STR,
        'char'      => \PDO::PARAM_STR,
        'varchar'   => \PDO::PARAM_STR,
        'text'      => \PDO::PARAM_STR,
        'longtext'  => \PDO::PARAM_STR,
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

    public static function _2hump($str, $ucFirst=true){
        $tmp = ucwords(strtr($str,['_'=>' ']));
        return $ucFirst ? strtr($tmp,[' '=>'']) : strtr(lcfirst($tmp), [' '=>'']);
    }

    public static function hump2_($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

}