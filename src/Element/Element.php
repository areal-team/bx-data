<?php

namespace Akop\Element;

\CModule::IncludeModule("iblock");

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
    protected $cachePath = "element/";
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
     * @param string $cachePath
     * @param bool|bool $noLimit - устанавливать в true при необходимости выборки без ограничений (это черевато нехваткой памяти)
     * @return void
     * @important
     */
    public function __construct(array $params = array())
    {
        $this->setIblockId($params);
        $this->noLimit = ( isset($params["noLimit"]) ? $params["noLimit"] : $this->noLimit );

        /* Не всегда нужно кэшировать подобные запросы, при сохранении кэша может вылететь ошибка.
        К тому же кэширование пусть будет на более высоком уровне */
        $this->arCache = array(
            "path" => ( isset($params["cachePath"]) ? $params["cachePath"] : $this->cachePath ),
            "cachePeriod" => ( isset($params["cachePeriod"]) ? $params["cachePeriod"] : $this->cachePeriod )
        );
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
        /* если нет ограничений на выборку, то выбрасываем исключение, в противном случае скрипт падает на нехватке памяти
            Можно ограничивать не только количество записей, но и число возвращаемых полей.
            При ограничении только выборкой полей, скрипту все равно может не хватить памяти.
            Обработать это не представляется возможным, оставляю на откуп пользователю класса.
        */

        $params = $this->params;
        if (( empty($params)
            || ( count($params["filter"])  == 0 ) && empty($params["limit"]) && empty($params["select"])
            ) && !$this->noLimit
        ) {
            throw new \Exception("Ограничьте выборку установив параметр 'filter', 'limit' или 'select'", 400);
        }


        $params["filter"]["IBLOCK_ID"] = $this->iblockId;
        /* Оставляем возможность выбрать неактивные элементы */
        if (!isset($params["filter"]["!ACTIVE"])) {
            $params["filter"]["ACTIVE"] = "Y";
        }
        /* По умолчанию возвращаем ассоциативный массив */
        if (!isset($params["isAssoc"])) {
            $params["isAssoc"] = true;
        }

        $cache = $this->_createCacheInstance(md5(json_encode($params)));
        if ($this->_isCacheExists()) {
            $result = $cache->GetVars();
        } else {
            if (!empty($params["limit"])) {
                $params["limit"] = array("nTopCount" => $params["limit"]);
            }

            $params = array_merge(
                array(
                    "order" => false,
                    "filter" => array("IBLOCK_ID" => $this->iblockId),
                    "group" => false,
                    "limit" => false,
                    "select" => $this->select,
                ),
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
            $obj = \CIBlockElement::GetList(
                $params["order"],
                $params["filter"],
                $params["group"],
                $params["limit"],
                $params["select"]
            );

            $result = array();
            while ($el = $obj->Fetch()) {
                $key = ( $params["isAssoc"]
                    ? $el["ID"]
                    : count($result)
                );
                $result[$key] = $this->getRenamed($el);
            }

            $this->_saveCache(
                $cache,
                $result
            );
        }
        return $result;
    }

    public function getMultiProperty($params)
    {
        $this->setIblockId($params);
        $result = array();
        echo $this->iblockId;
        $db_props = \CIBlockElement::GetProperty($this->iblockId, $params['id'], $params['order'], $params['filter']);
        while ($ar_props = $db_props->Fetch()) {
            $res = \CIBlockElement::GetByID($ar_props['VALUE']);
            $prop = $res->GetNext();
            $result[] = $prop;
        }
        return $result;
    }

    public function getRow(array $params = array())
    {
        $params["limit"] = 1;
        $result = $this->getList($params);
        return is_array($result)
            ? current($result)
            : false;
    }

    public function getMap()
    {
        return array_merge(
            $this->fieldsBase,
            $this->fieldsExtra
        );
    }

    public function add($params)
    {
        $this->beforeAdd();
        $params = $this->getUpdatedParamsFromArray($params);

        foreach ($params as $fieldName => $value) {
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
        $this->afterAdd();
        return $id;
    }


    public function updateProperties($id, array $params = array())
    {
        if ($id > 0) {
            \CIBlockElement::SetPropertyValuesEx($id, false, $params);
        }
    }

    public function update($id, array $params = array())
    {
        $this->beforeUpdate();
        $params = $this->getUpdatedParamsFromArray($params);
        foreach ($params as $fieldName => $value) {
            $code = $this->getPropertyCodeByProperty($fieldName);
            if ($code) {
                $finalParams["PROPERTY_VALUES"][$code] = $value;
            } else {
                $finalParams[$fieldName] = $value;
            }
        }
        $obj = new \CIBlockElement;
        $id = $obj->Update($id, $finalParams);
        if (!$id) {
            throw new \Exception($obj->LAST_ERROR . PHP_EOL . print_r($finalParams, true), 400);
        }
        $this->afterAdd();
        return $id;
    }


    public function updateProperty($id, $nameProperty, $valueProperty)
    {
        if (!empty($id)) {
            \CIBlockElement::SetPropertyValuesEx(
                $id,
                $this->iblockId,
                [$this->getPropertyCode($nameProperty) => $valueProperty]
            );
        }
    }


    public function getPropertyCode($nameProperty)
    {
        return ( ( stripos($this->fields[$nameProperty], "PROPERTY_") !== false )
            ? substr($this->fields[$nameProperty], 9)
            : false
        );
    }

    public function getPropertyCodeByProperty($nameProperty)
    {
        return ( ( stripos($nameProperty, "PROPERTY_") !== false )
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
        return ( ( stripos($value, "PROPERTY_") !== false )
            ? $value . "_VALUE"
            : $value
        );
    }
}
