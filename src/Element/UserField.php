<?
namespace Akop\Element;

use Bitrix\Main\Entity;

class UserField extends BaseElement
{

    public function getList(array $params = array())
    {
    	$result = array();
		if ( !is_array($params["filter"]) ) {
			$params["filter"] = array();
		}
    	if ( isset($params["filter"]["blockName"]) ) {
    		$hl = new \Gb\Element\HlElement(array("blockName" => $params["filter"]["blockName"]));
	   		$params["filter"]["ENTITY_ID"] = "HLBLOCK_" . $hl->getBlockId();
    	}

		$obj = \CUserTypeEntity::GetList(
			$params["order"],
			$params["filter"]
		);
		while($el = $obj->Fetch()) {
			$result[$el["ID"]] = $el;
		}

		$result = $this->filter($result, $params["filter"]["SETTINGS"]);
		return $result;
    }

    private function filter(array $items = array(), $filter)
    {
		if ( empty($filter) ) {
			$result = $items;
		} else {
	    	$result = array_filter($items, function($item) use ($filter) {
	    		$result = true;
	    		foreach ($filter as $key => $value) {
	    			$result = $result && ($item["SETTINGS"][$key] == $value );
	    		}
	    		return $result;
	    	});
	    }
		return $result;
    }
}
