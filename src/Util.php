<?
namespace Akop;

class Util
{

	static function getResult($result, $res)
	{
		$resultKey = ( $res )
			? "updated"
			: "failed";
		$result[$resultKey]++;

		return $result;
	}


	static function pre($var, $title = '')
	{
		echo '<h3>'.$title.'</h3>';
		if ( is_array($var) ) {
			echo 'count = ' . count($var) . '<br>';

		}
		echo '<pre>';
		print_r($var);
		echo '</pre>';
	}

	static function getLastQuery()
	{
		\Bitrix\Main\Loader::includeModule("iblock");
		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\ElementTable::getEntity());
		self::pr_var($query->getLastQuery(), 'Last query');
	}

	static function getQueryDump()
	{
		\Bitrix\Main\Loader::includeModule("iblock");
		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\ElementTable::getEntity());
		self::pr_var($query->dump(), 'Query dump');
	}

	static function camelize($input, $separator = '_')
	{
    	return lcfirst(join(array_map('ucfirst', explode('_', strtolower($input)))));
	}

	static function toTranslit($str)
	{
		return strtolower( str_replace(" ", "-", $str) );
	}

	static function fromTranslit($str)
	{
		return strtolower( str_replace("-", " ", $str) );
	}

	static function getTransformedArray($array, $key)
	{
		foreach ($array as $value) {
			$result[$value[$key]] = $value;
		}
		return $result;
	}
}