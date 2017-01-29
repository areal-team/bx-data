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

    public function __construct(array $params = array())
    {
        \CModule::IncludeModule("iblock");
        $this->setIblockId($params);
        parent::__construct();
    }



    public function getList(array $params = array())
    {
        if (!empty($params["limit"])) {
            $params["limit"] = array("nTopCount" => $params["limit"]);
        }
        parent::getList($params);

        if (!empty($params["filter"]["IBLOCK_SECTION_ID"])) {
            $obj = \CIBlockSection::GetByID($params["filter"]["IBLOCK_SECTION_ID"]);
            if ($section = $obj->Fetch()) {
                unset($this->params["filter"]["IBLOCK_SECTION_ID"]);
                $this->params["filter"]["LEFT_MARGIN"] = $section["LEFT_MARGIN"] + 1;
                $this->params["filter"]['RIGHT_MARGIN'] = $section["RIGHT_MARGIN"] - 1;
            }
        }

        $obj = \CIBlockSection::GetList(
            $this->params["order"],
            $this->params["filter"],
            $this->params["count"],
            $this->params["select"],
            $this->params["limit"]
        );

        while ($section = $obj->GetNext(true, false)) {
            $result[$section['ID']] = $section;
        }

        return $result;
    }

    public function add($params)
    {
        parent::add($params);
        $bs = new \CIBlockSection;
        if (empty($params["IBLOCK_ID"])) {
            $params["IBLOCK_ID"] = $this->iblockId;
        }
        $id = $bs->Add($params);
        if (!$id) {
            throw new \Exception($bs->LAST_ERROR . PHP_EOL . print_r($params, true), 400);
        }
        return $id;
    }
}
