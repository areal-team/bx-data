<?php
namespace Akop\Element;

class IbElementOrSection extends AbstractElement
{
    protected $iblockCode = false;
    protected $iblockId = false;

    public function __construct()
    {
        \CModule::IncludeModule("iblock");
        $this->setIblockId();
        parent::__construct();
    }

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

    public function add(array $params)
    {
        if (empty($params["IBLOCK_ID"])) {
            $params["IBLOCK_ID"] = $this->iblockId;
        }
        parent::add($params);
    }

    /**
     * Устанавливает id инфоблока по переданным параметрам
     * @param array $params
     */
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
