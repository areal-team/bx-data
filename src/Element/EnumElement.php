<?php
namespace Akop\Element;

\CModule::IncludeModule("iblock");

class EnumElement extends AbstractElement
{

    public function __construct($params)
    {
        $this->iblockId = $params["iblockId"];
        $this->code = $params["code"];
    }

    public function getList(array $params = array())
    {
        parent::getList($params);
        $params = array_merge(
            array(
                "order" => false,
                "filter" => array(
                    "IBLOCK_ID" => $this->iblockId,
                    "CODE" => $this->code,
                ),
            ),
            $params
        );

        $objProp = \CIBlockPropertyEnum::GetList(
            $params["order"],
            $params["filter"]
        );
        while ($prop = $objProp->Fetch()) {
            $result[$prop['ID']] = $prop;
        }
        return $result;
    }
}
