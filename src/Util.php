<?php
namespace Akop;

class Util
{

    public static function getResult($result, $res)
    {
        $resultKey = ( $res )
            ? "updated"
            : "failed";
        $result[$resultKey]++;

        return $result;
    }


    public static function pre($var, $title = '')
    {
        echo '<h3>'.$title.'</h3>';
        if (is_array($var)) {
            echo 'count = ' . count($var) . '<br>';

        }
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }

    public static function getLastQuery()
    {
        \Bitrix\Main\Loader::includeModule("iblock");
        $query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\ElementTable::getEntity());
        self::pr_var($query->getLastQuery(), 'Last query');
    }

    public static function getQueryDump()
    {
        \Bitrix\Main\Loader::includeModule("iblock");
        $query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\ElementTable::getEntity());
        self::pr_var($query->dump(), 'Query dump');
    }

    public static function camelize($input, $separator = '_')
    {
        return lcfirst(join(array_map('ucfirst', explode('_', strtolower($input)))));
    }

    public static function toTranslit($str)
    {
        return strtolower(str_replace(" ", "-", $str));
    }

    public static function fromTranslit($str)
    {
        return strtolower(str_replace("-", " ", $str));
    }

    public static function getTransformedArray($array, $key)
    {
        foreach ($array as $value) {
            $result[$value[$key]] = $value;
        }
        return $result;
    }
}
