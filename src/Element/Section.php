<?php
namespace Akop\Element;

class Section extends IbElementOrSection
{
    protected $fields = [
        "ID",
        "TIMESTAMP_X",
        "MODIFIED_BY",
        "DATE_CREATE",
        "CREATED_BY",
        "IBLOCK_ID",
        "IBLOCK_SECTION_ID",
        "ACTIVE",
        "GLOBAL_ACTIVE",
        "SORT",
        "NAME",
        "PICTURE",
        "LEFT_MARGIN",
        "RIGHT_MARGIN",
        "DEPTH_LEVEL",
        "DESCRIPTION",
        "DESCRIPTION_TYPE",
        "SEARCHABLE_CONTENT",
        "CODE",
        "XML_ID",
        "TMP_ID",
        "DETAIL_PICTURE",
        "SOCNET_GROUP_ID",
        "LIST_PAGE_URL",
        "SECTION_PAGE_URL",
        "IBLOCK_TYPE_ID",
        "IBLOCK_CODE",
        "IBLOCK_EXTERNAL_ID",
        "EXTERNAL_ID",
    ];

    public function getList(array $params = [])
    {
        parent::getList($params);

        $obj = new \CIBlockSection;
        if (!empty($params["filter"]["IBLOCK_SECTION_ID"])) {
            $sectionObj = $obj->GetByID($params["filter"]["IBLOCK_SECTION_ID"]);
            if ($section = $sectionObj->Fetch()) {
                unset($this->params["filter"]["IBLOCK_SECTION_ID"]);
                $this->params["filter"]["LEFT_MARGIN"] = $section["LEFT_MARGIN"] + 1;
                $this->params["filter"]['RIGHT_MARGIN'] = $section["RIGHT_MARGIN"] - 1;
            }
        }

        $list = $obj->GetList(
            $this->params["order"],
            $this->params["filter"],
            $this->params["count"],
            $this->params["select"],
            $this->params["limit"]
        );

        while ($section = $list->GetNext(true, false)) {
            $result[$section['ID']] = $section;
        }

        return $result;
    }

    public function add(array $params)
    {
        parent::add($params);
        // \Akop\Util::pre($this->params, 'add $params');
        $obj = new \CIBlockSection;
        $primaryKey = $obj->Add($this->params);
        if (!$primaryKey) {
            throw new \Exception($obj->LAST_ERROR . PHP_EOL . print_r($params, true), 400);
        }
        return $primaryKey;
    }

    /**
     * Возвращает массив SEO для раздела с ключами:
     *      SECTION_META_TITLE
     *      SECTION_META_KEYWORDS
     *      SECTION_META_DESCRIPTION
     *      SECTION_PAGE_TITLE
     * @param int $primaryKey
     */
    public function getSEO($sectionId)
    {
        $ipropValues = new \Bitrix\Iblock\InheritedProperty\InheritedProperty\SectionValues(
            $this->iblockId,
            $sectionId
        );
        return $ipropValues->getValues();
    }
}
