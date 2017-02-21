<?php

namespace Akop\Element;

/**
 * Класс описывает функции для работы с элементами инфоблоков в стиле ядра D7
 * @author: Андрей Копылов <aakopylov@mail.ru>
 * @license MIT
 */
class Element extends IbElementOrSection
{
    protected $fieldsBase = [
        "id" => "ID",
        "timestampX" => "TIMESTAMP_X",
        "modifiedBy" => "MODIFIED_BY",
        "dateCreate" => "DATE_CREATE",
        "createdBy" => "CREATED_BY",
        "sectionId" => "SECTION_ID",
        "active" => "ACTIVE",
        "sort" => "SORT",
        "name" => "NAME",
        "picture" => "PICTURE",
        "leftMargin" => "LEFT_MARGIN",
        "rightMargin" => "RIGHT_MARGIN",
        "depthLevel" => "DEPTH_LEVEL",
        "description" => "DESCRIPTION",
        "code" => "CODE",
        "xmlId" => "XML_ID",
        "detailPicture" => "DETAIL_PICTURE",
        "externalId" => "EXTERNAL_ID",
    ];

    /**
     * Возвращает массив элементов инфоблока в соответствии с переданными паметрами
     * Можем вернуть ассоциативный массив или простой.
     * Для получения простого массива необходимо передать параметр isAssoc = false
     * В некоторых случаях возврат простого массива сильно экономит время и память.
     * Например, при возврате массива данных состоящих только из одного поля этот массив можно сразу передать
     * в другой запрос без дополнительных преобразований.
     * @param array $params массив со стандартными элементами для GetList: "order","filter","group","limit","select"
     * @return array
     */
    public function getList(array $params = array())
    {
        parent::getList($params);
        $params = $this->params;

        /* По умолчанию возвращаем ассоциативный массив */
        if (!isset($params["isAssoc"])) {
            $params["isAssoc"] = true;
        }

        $params = array_merge(
            [
                "order" => false,
                "filter" => array("IBLOCK_ID" => $this->iblockId),
                "group" => false,
                "limit" => false,
                "select" => $this->select,
            ],
            $params
        );


        /**
         * В запросах обязательно должен присутствовать ID для того чтобы вернуть ассоциативный массив
         * Если возвращаем обычный массив, то поле ID не добавляем
         */
        if (!in_array("ID", $params["select"]) && $params["isAssoc"]) {
            $params["select"][] = "ID";
        }

        // \CDebug::add($params, 'Element::getList params' . microtime());
        $obj = new \CIBlockElement;
        $list = $obj->GetList(
            $params["order"],
            $params["filter"],
            $params["group"],
            $params["limit"],
            $params["select"]
        );

        $result = [];
        while ($elem = $list->Fetch()) {
            $key = ($params["isAssoc"]
                    ? $elem["ID"]
                    : count($result)
                );
            $result[$key] = $this->getRenamed($elem);
        }

        return $result;
    }

    public function getMultiProperty($params)
    {
        $this->setIblockId($params);
        $result = array();
        // echo $this->iblockId;
        $obj = new \CIBlockElement;
        $dbProps = $obj->GetProperty($this->iblockId, $params['id'], $params['order'], $params['filter']);
        while ($props = $dbProps->Fetch()) {
            $res = $obj->GetByID($props['VALUE']);
            $prop = $res->GetNext();
            $result[] = $prop;
        }
        return $result;
    }

    public function getMap()
    {
        return array_merge(
            $this->fieldsBase,
            $this->fieldsExtra
        );
    }

    public function add(array $params)
    {
        parent::add($params);

        foreach ($this->params as $fieldName => $value) {
            $code = $this->getPropertyCodeByProperty($fieldName);
            if ($code) {
                $finalParams["PROPERTY_VALUES"][$code] = $value;
            } else {
                $finalParams[$fieldName] = $value;
            }
        }

        $obj = new \CIBlockElement;
        $primaryKey = $obj->Add($finalParams);
        if (!$primaryKey) {
            throw new \Exception($obj->LAST_ERROR . PHP_EOL . print_r($finalParams, true), 400);
        }
        return $primaryKey;
    }


    public function updateProperties($primaryKey, array $params = [])
    {
        if ($primaryKey > 0) {
            $obj = new \CIBlockElement;
            $obj->SetPropertyValuesEx($primaryKey, false, $params);
        }
    }

    public function update($primaryKey, array $params)
    {
        parent::update($primaryKey, $params);
        $finalParams = $this->getUpdateParams($this->params);
        $obj = new \CIBlockElement;
        $primaryKey = $obj->Update($primaryKey, $finalParams);
        if (!$primaryKey) {
            throw new \Exception($obj->LAST_ERROR . PHP_EOL . print_r($finalParams, true), 400);
        }
        return $primaryKey;
    }

    /**
     * @param array $params
     */
    private function getUpdateParams(array $params)
    {
        foreach ($params as $fieldName => $value) {
            $code = $this->getPropertyCodeByProperty($fieldName);
            if ($code) {
                $result["PROPERTY_VALUES"][$code] = $value;
            } else {
                $result[$fieldName] = $value;
            }
        }
        return $result;
    }


    public function updateProperty($primaryKey, $nameProperty, $valueProperty)
    {
        if (!empty($primaryKey)) {
            $obj = new \CIBlockElement;
            $obj->SetPropertyValuesEx(
                $primaryKey,
                $this->iblockId,
                [$this->getPropertyCode($nameProperty) => $valueProperty]
            );
        }
    }


    private function getPropertyCode($nameProperty)
    {
        return ((stripos($this->fields[$nameProperty], "PROPERTY_") !== false)
            ? substr($this->fields[$nameProperty], 9)
            : false
        );
    }

    private function getPropertyCodeByProperty($nameProperty)
    {
        return ((stripos($nameProperty, "PROPERTY_") !== false)
            ? substr($nameProperty, 9)
            : false
        );
    }

    public function getRealFieldName($nameProperty)
    {
        return $this->fields[$nameProperty];
    }

    protected function updateValueForReverse($value)
    {
        return ((stripos($value, "PROPERTY_") !== false)
            ? $value . "_VALUE"
            : $value
        );
    }

    /**
     * Возвращает массив SEO для элемента с ключами:
     *      ELEMENT_META_TITLE
     *      ELEMENT_META_KEYWORDS
     *      ELEMENT_META_DESCRIPTION
     *      ELEMENT_PAGE_TITLE
     * @param int $primaryKey
     */
    public function getSEO($elementId)
    {
        $ipropValues = new \Bitrix\Iblock\InheritedProperty\InheritedProperty\ElementValues(
            $this->iblockId,
            $elementId
        );
        return $ipropValues->getValues();
    }
}
