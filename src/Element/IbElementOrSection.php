<?php
namespace Akop\Element;

\CModule::IncludeModule("iblock");

class IbElementOrSection extends AbstractElement
{
    protected $iblockCode = false;
    protected $iblockId = false;

    public function getList(array $params = array())
    {
        $params["filter"]["IBLOCK_ID"] = $this->iblockId;
        /* Оставляем возможность выбрать неактивные элементы */
        if (!isset($params["filter"]["!ACTIVE"])) {
            $params["filter"]["ACTIVE"] = "Y";
        }

        if (!empty($params["limit"])) {
            $params["limit"] = array("nTopCount" => $params["limit"]);
        }
        parent::getList($params);
    }

    protected function setIblockId($params = [])
    {
        if (!empty($params['iblockCode'])) {
            $this->iblockCode = $params['iblockCode'];
        }

        $this->iblockId = ( ( !empty($this->iblockCode) )
            ? $this->getIblockIdByCode($this->iblockCode)
            : $params["iblockId"]
        );
    }

    protected function getIblockIdByCode($iblockCode)
    {
        $result = false;
        $iblock = \CIBlock::GetList(false, array('CODE' => $iblockCode));
        if ($res = $iblock->Fetch()) {
            $result = $res['ID'];
        }
        return $result;
    }
}
