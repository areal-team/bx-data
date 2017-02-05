<?php
namespace Akop;

class Util
{

    public static function pre($var, $title = '')
    {
        if (php_sapi_name() != "cli") {
            echo '<h3>'.$title.'</h3>';
            if (is_array($var)) {
                echo 'count = ' . count($var) . '<br>';

            }
            echo '<pre>';
            print_r($var);
            echo '</pre>';
        } else {
            echo PHP_EOL . $title . PHP_EOL;
            if (is_array($var)) {
                echo 'count = ' . count($var) . PHP_EOL;
            }
            print_r($var);
        }
    }

    private static function printTag($tag, $isCLI)
    {
        if (!$isCLI) {
            echo $tag;
        }
    }

    public static function showLastQuery()
    {
        self::pre(self::getLastQuery(), 'Last query');
    }

    public static function getLastQuery()
    {
        \Bitrix\Main\Loader::includeModule("iblock");
        $query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\ElementTable::getEntity());
        return $query->getLastQuery();
    }

    public static function showQueryDump()
    {
        self::pre(self::getQueryDump(), 'Query dump');
    }

    public static function getQueryDump()
    {
        \Bitrix\Main\Loader::includeModule("iblock");
        $query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\ElementTable::getEntity());
        return $query->dump();
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
