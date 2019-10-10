<?php

namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class HlElement extends AbstractElement
{
    protected $prefix = "";
    protected $entityName = "";
    protected $softDelete = false;
    protected $fieldsBase = [
        "id" => "ID",
    ];

    // private $id = false;
    private $hlblocks = [];
    private $hlblockId = false;
    private $entityDC;

    /**
     * @param $params array entityName или blockName
     */
    public function __construct(array $params = [])
    {
        if (!empty($params)) {
            $this->entityName = (isset($params["blockName"])
                ? substr($params["blockName"], strlen($this->prefix))
                : $params["entityName"]
            );
        }
        $this->hlblockObj = new HlBlock;
        $this->getHlBlocks();
        $this->hlblockId = $this->getHlBlockIdByName($this->prefix . $this->entityName);
        // \Akop\Util::pre($this, 'HlElement __construct');
        $this->entityDC = $this->hlblockObj->getEntityDataClass(['ID' => $this->hlblockId]);
        parent::__construct();
        return $this;
    }


    public function getList(array $params = array())
    {
        // $params['select'] = [];
        parent::getList($params);
        $availableKeys = [
            'select',
            'filter',
            'group',
            'order',
            'limit',
            'offset',
            'runtime',
            'cache',
        ];
        $getListParams = array_filter($this->params, function ($item) use ($availableKeys) {
            return in_array($item, $availableKeys);
        }, ARRAY_FILTER_USE_KEY);
        $res = $this->entityDC->getList($getListParams);
        $result = [];
        while ($item = $res->Fetch()) {
            $key = (isset($item["ID"]) && $this->isAssoc)
                ? $item["ID"]
                : count($result);
            $result[$key] = $this->getProcessed($item);
        }
        return $result;
    }

    public function getRowByName($name)
    {
        return $this->getRow(["filter" => ["name" => $name]]);
    }

    public function add(array $params)
    {
        parent::add($params);
        $result = $this->entityDC->add($this->params);
        return $result->getId();
    }

    public function delete($primaryKey)
    {
        if (!parent::delete($primaryKey)) {
            return false;
        }

        if ($this->softDelete) {
            return $this->update($primaryKey, ['UF_DELETED' => 1]);
        }

        $res = $this->entityDC->delete($primaryKey);
        return $res->isSuccess();
    }

    public function undelete($primaryKey)
    {
        $result = false;
        if ($this->softDelete) {
            $result = $this->update($primaryKey, ['UF_DELETED' => 0]);
            $this->setLastOperation('undelete');
        }
        return $result;
    }

    public function update($primaryKey, array $params)
    {
        parent::update($primaryKey, $params);
        // \Akop\Util::pre([$primaryKey, $this->params], 'HlElement update');
        $this->entityDC->update($primaryKey, $this->params);
        return $primaryKey;
    }

    public function getBlockId()
    {
        return $this->hlblockId;
    }

    public function getMap()
    {
        $userFieldsObj = new UserField;
        $userFields = $userFieldsObj->getList([
            'order' => ["ENTITY_ID" => "ASC"],
            'filter' => ["ENTITY_ID" => "HLBLOCK_" . $this->hlblockId]
        ]);
        return $this->getMapFields($userFields);
    }

    private function getMapFields(array $userFields)
    {
        $result = $this->fieldsBase;
        foreach ($userFields as $field) {
            $alias = \Akop\Util::camelize(substr($field["FIELD_NAME"], 3));
            switch ($field["USER_TYPE_ID"]) {
                case "hlblock":
                    $result = array_merge(
                        $result,
                        $this->addRefFields($field, $alias)
                    );
                    $alias .= "Id";
                    break;
                default:
                    if (!in_array($alias, $this->fieldsIgnore) && !in_array($fieldName, $this->fieldsIgnore)) {
                        $result[$alias] = $fieldName;
                    }
                    break;
            }
            $result[$alias] = $field["FIELD_NAME"];
        }
        return $result;
    }

    protected function addRefFields($field, $alias)
    {
        $hlBlockId = $field["SETTINGS"]["HLBLOCK_ID"];
        $hlBlockName = $this->hlblocks[$hlBlockId];
        $refFields = $this->getRefFields($hlBlockId);
        if (!empty($refFields)) {
            foreach ($refFields as $refField) {
                $result[$alias . $refField['alias']] = [
                    "name" => $refField['fieldname'],
                    "data_type" => "\\" . $hlBlockName,
                    "reference" => [
                        "=this." . $field["FIELD_NAME"] => "ref.ID"
                    ],
                ];
            }
        }
        return $result;
    }

    /**
     * Возвращает поля справочника
     * @todo получать все поля справочника из БД
     */
    protected function getRefFields()
    {
        return [
            ['alias' => 'Name', 'fieldname' => 'UF_NAME'],
        ];
    }

    /**
     * Выясняем возможно ли удаление данного элемента
     *
     * Если есть записи в HL блоках со ссылкой на удаляемый элемент,
     * то запись не должна быть удалена
     *
     * @param  int  $primaryKey
     * @return boolean
     * @todo Добавить обработку инфоблоков
     */
    protected function isDeletable($primaryKey)
    {
        $obj = new UserField;
        $fields = $obj->getList(array(
            "filter" => array(
                "USER_TYPE_ID" => "hlblock",
                "SETTINGS" => array(
                    "HLBLOCK_ID" => $this->hlblockId
                )
            )
        ));

        $result = true;
        foreach ($fields as $field) {
            $obj = new HlElement(array(
                "hlblockId" => $this->removePrefix($field["ENTITY_ID"])
            ));
            $list = $obj->getList(array(
                "filter" => array(
                    $field["FIELD_NAME"] => $primaryKey
                )
            ));

            if (!empty($list)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * Убирает префикс "HLBLOCK_"
     */
    private function removePrefix($fieldname)
    {
        return substr($fieldname, 8);
    }

    protected function updateParamsFilter()
    {
        parent::updateParamsFilter();
        if ($this->softDelete) {
            $this->params['filter']['UF_DELETED'] = 0;
        }
    }

    private function getHlBlocks()
    {
        $list = $this->hlblockObj->getList(['select' => ['NAME']]);
        foreach ($list as $value) {
            $this->hlblocks[$value["ID"]] = $value["NAME"];
        }
        return $this->hlblocks;
    }

    private function getHlBlockIdByName($hlblockName)
    {
        return array_search($hlblockName, $this->hlblocks);
    }
}
