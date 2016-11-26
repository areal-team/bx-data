<?php
namespace Akop\Element;

\CModule::IncludeModule("iblock");

class IbElementOrSection extends BaseElement
{
    protected $iblockCode = false;
    protected $iblockId = false;

    protected function setIblockId($params)
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
