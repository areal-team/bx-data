<?php

namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class HlElement extends BaseElement
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
        parent::getList($params);
        // \Akop\Util::pre($this->params, 'HlElement getList $this->params');
        $res = $this->entityDC->getList($this->params);
        while ($item = $res->Fetch()) {
            $key = (isset($item["ID"]))
                    ? $item["ID"]
                    : count($result);

            $result[$key] = $this->getRenamed($item);
        }

        return $result;
    }

    public function getRowByName($name)
    {
        return $this->getRow(["filter" => ["name" => $name]]);
    }

    protected function addImplement(array $params)
    {
        $result = $this->entityDC->add($params);
        return $result->getId();
    }

    public function delete($primaryKey)
    {
        $this->startNewOperation('delete');
        if (!$this->isDeletable($primaryKey)) {
            $this->setErrorMessage("Удаление невозможно. Существуют зависимые объекты.");
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

    protected function updateImplement($primaryKey, array $params)
    {
        $this->entityDC->update($primaryKey, $params);
        return $primaryKey;
    }

    public function getBlockId()
    {
        return $this->hlblockId;
    }

    public function getMap()
    {
        $result = $this->fieldsBase;
        $userFieldsObj = new UserField;
        $userFields = $userFieldsObj->getList([
            'order' => ["ENTITY_ID" => "ASC"],
            'filter' => ["ENTITY_ID" => "HLBLOCK_" . $this->hlblockId]
        ]);
        foreach ($userFields as $field) {
            $alias = \Akop\Util::camelize(substr($field["FIELD_NAME"], 3));
            switch ($field["USER_TYPE_ID"]) {
                case "hlblock":
                    // список возможных значений
                    $result[$alias . "Name"] = array(
                        "name" => "UF_NAME",
                        "data_type" => "\\" . $this->hlblocks[$field["SETTINGS"]["HLBLOCK_ID"]],
                        "reference" => array(
                            "=this." . $field["FIELD_NAME"] => "ref.ID"
                        ),
                    );
                    $alias .= "Id";
                    break;
                default:
                    break;
            }
            $result[$alias] = $field["FIELD_NAME"];
        }

        return $result;
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
                "hlblockId" => substr($field["ENTITY_ID"], 8) // убираем "HLBLOCK_"
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
