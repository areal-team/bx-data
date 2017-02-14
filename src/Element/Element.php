<?php

namespace Akop\Element;

/**
 * Класс описывает функции для работы с Элементами инфоблоков в более простом стиле
 * @author: Андрей Копылов
 * @mail: aakopylov@mail.ru,
 * @skype: andrew.kopylov.74
 */
class Element extends IbElementOrSection
{
    protected $rename = [];
    protected $select = false;
    protected $noLimit = false;
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
     * @param integer $iblockId
     * @param bool $noLimit - устанавливать в true при необходимости выборки без ограничений
     *                        (это черевато нехваткой памяти)
     * @return void
     * @important
     */
    public function __construct(array $params = array())
    {
        \CModule::IncludeModule("iblock");
        $this->setIblockId($params);
        $this->noLimit = (isset($params["noLimit"]) ? $params["noLimit"] : $this->noLimit);

        parent::__construct();
    }

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
        /* если нет ограничений на выборку, то выбрасываем исключение, в противном случае скрипт падает на нехватке памяти
            Можно ограничивать не только количество записей, но и число возвращаемых полей.
            При ограничении только выборкой полей, скрипту все равно может не хватить памяти.
            Обработать это не представляется возможным, оставляю на откуп пользователю класса.
        */
/*
        if ((empty($params)
            || (count($params["filter"])  == 0) && empty($params["limit"]) && empty($params["select"])
            ) && !$this->noLimit
        ) {
            throw new \Exception("Ограничьте выборку установив параметр 'filter', 'limit' или 'select'", 400);
        }
*/
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
        while ($el = $list->Fetch()) {
            $key = ($params["isAssoc"]
                    ? $el["ID"]
                    : count($result)
                );
            $result[$key] = $this->getRenamed($el);
        }

        return $result;
    }

    public function getMultiProperty($params)
    {
        $this->setIblockId($params);
        $result = array();
        // echo $this->iblockId;
        $dbProps = \CIBlockElement::GetProperty($this->iblockId, $params['id'], $params['order'], $params['filter']);
        while ($props = $dbProps->Fetch()) {
            $res = \CIBlockElement::GetByID($props['VALUE']);
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
        if (empty($finalParams["IBLOCK_ID"])) {
            $finalParams["IBLOCK_ID"] = $this->iblockId;
        }
        $id = $obj->Add($finalParams);
        if (!$id) {
            throw new \Exception($obj->LAST_ERROR . PHP_EOL . print_r($finalParams, true), 400);
        }
        return $id;
    }


    public function updateProperties($primaryKey, array $params = [])
    {
        if ($primaryKey > 0) {
            \CIBlockElement::SetPropertyValuesEx($primaryKey, false, $params);
        }
    }

    public function update($primaryKey, array $params)
    {
        parent::update($primaryKey, $params);
        foreach ($this->params as $fieldName => $value) {
            $code = $this->getPropertyCodeByProperty($fieldName);
            if ($code) {
                $finalParams["PROPERTY_VALUES"][$code] = $value;
            } else {
                $finalParams[$fieldName] = $value;
            }
        }
        $obj = new \CIBlockElement;
        $primaryKey = $obj->Update($primaryKey, $finalParams);
        if (!$primaryKey) {
            throw new \Exception($obj->LAST_ERROR . PHP_EOL . print_r($finalParams, true), 400);
        }
        return $primaryKey;
    }


    public function updateProperty($primaryKey, $nameProperty, $valueProperty)
    {
        if (!empty($primaryKey)) {
            \CIBlockElement::SetPropertyValuesEx(
                $primaryKey,
                $this->iblockId,
                [$this->getPropertyCode($nameProperty) => $valueProperty]
            );
        }
    }


    public function getPropertyCode($nameProperty)
    {
        return ((stripos($this->fields[$nameProperty], "PROPERTY_") !== false)
            ? substr($this->fields[$nameProperty], 9)
            : false
        );
    }

    public function getPropertyCodeByProperty($nameProperty)
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
}
