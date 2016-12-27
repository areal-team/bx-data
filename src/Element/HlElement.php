<?php
namespace Akop\Element;

\CModule::includeModule('highloadblock');

use \Bitrix\Highloadblock as HL;
use \Akop\Element as Element;

/**
 * @author Андрей Копылов aakopylov@mail.ru
 */
class HlElement extends BaseElement
{
    protected $prefix = "";
    protected $primaryKey = "id";
    protected $entityName = "";
    protected $softDelete = false;
    protected $fieldsBase = [
        "id" => "ID",
    ];

    // private $id = false;
    // private $item = false;
    private $hlblockId = false;
    private $hlblockName = false;
    private $entityDC;

    public function __construct(array $params = array())
    {
        if (!empty($params)) {
            // В зависимости от переданных параметров формируем фильтр и сообщение об ошибке
            if (isset($params["hlblockId"])) {
                $this->hlblockId = $params["hlblockId"];
                $filter = array("ID" => $this->hlblockId);
                $errMesssage = "ID=" . $this->hlblockId;
            } else {
                if (isset($params["blockName"])) {
                    $this->entityName = substr($params["blockName"], strlen($this->prefix));
                } elseif (isset($params["entityName"])) {
                    $this->entityName = $params["entityName"];
                }
            }
        }

        $filter = array("NAME" => $this->prefix . $this->entityName);
        $errMesssage = "NAME=" . $this->prefix . $this->entityName;

        $objBlock = HL\HighloadBlockTable::getList(array(
            "filter" => $filter
        ));
        $blockEl = $objBlock->Fetch();
        try {
            $this->hlblockId = $blockEl["ID"];
            $this->hlblockName = $blockEl["NAME"];
        } catch (Exception $e) {
            throw new Exception("Не найден Highload block  {$errMesssage}", 404);
        }
        $entity = HL\HighloadBlockTable::compileEntity($blockEl);
        $edc = (string) $entity->getDataClass();
        $this->entityDC = new $edc;
        // $this->id = $id;

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
        return $this->getRow(array(
                    "filter" => array(
                        "name" => $name
                    )
                ));
    }

    public function add(array $params)
    {
        $this->beforeAdd();
        $params = $this->compressFields($params);
        $params = $this->getUpdatedParamsFromArray($params);
        $result = $this->entityDC->add($params);
        $primaryKey = $result->getId();
        $this->afterAdd();
        return $primaryKey;
    }

    public function delete($primaryKey)
    {
        $this->beforeDelete();
        if (!$this->isDeletable($primaryKey)) {
            $result = false;
            $this->setLastOperation('delete_error');
            $this->setErrorMessage("Удаление невозможно. Существуют зависимые объекты.");
        } else {
            if ($this->softDelete) {
                $result = $this->update($primaryKey, array('UF_DELETED' => 1));
                $this->setLastOperation('soft_delete');
            } else {
                $res = $this->entityDC->delete($primaryKey);
                parent::afterDelete();
                $result = $res->isSuccess();
            }
        }
        return $result;
    }

    public function undelete($primaryKey)
    {
        $result = false;
        if ($this->softDelete) {
            $result = $this->update($primaryKey, array('UF_DELETED' => 0));
            $this->setLastOperation('undelete');
        }
        return $result;
    }

    public function update($primaryKey, array $params)
    {
        $this->beforeUpdate();
        $params = $this->compressFields($params);
        $params = $this->getUpdatedParamsFromArray($params);
        $this->entityDC->update($primaryKey, $params);
        $this->afterUpdate();
        return $primaryKey;
    }

    public function getBlockId()
    {
        return $this->hlblockId;
    }

    private function getObjectName()
    {
        return "HLBLOCK_" . $this->hlblockId;
    }

    public function getFields()
    {
        global $USER_FIELD_MANAGER;
        return $USER_FIELD_MANAGER->GetUserFields($this->getObjectName());
    }
/*
    private function getFieldId($fieldName)
    {
        $fields = $this->getFields();
        return $fields[$fieldName]["ID"];
    }
    private function getEnumValues($fieldId)
    {
        $obj = new CUserFieldEnum();
        $list = $obj->GetList(
            array(),
            array("USER_FIELD_ID" => $fieldId)
        );
        while ($el = $list->Fetch()) {
            $result[$el["ID"]] = $el;
        }
        return $result;
    }
    */

    public function getMap()
    {
        $result = $this->fieldsBase;

        $list = $this->getListBlocks();
        foreach ($list as $value) {
            $listBlocks[$value["ID"]] = $value["NAME"];
        }

        $blockId = array_search($this->hlblockName, $listBlocks);

        $obj = \CUserTypeEntity::GetList(
            array("ENTITY_ID" => "ASC"),
            array("ENTITY_ID" => "HLBLOCK_" . $blockId)
        );
        while ($item = $obj->Fetch()) {
            $alias = \Akop\Util::camelize(substr($item["FIELD_NAME"], 3));
            switch ($item["USER_TYPE_ID"]) {
                case "hlblock":
                    // список возможных значений
                    $result[$alias . "Name"] = array(
                        "name" => "UF_NAME",
                        "data_type" => "\\" . $listBlocks[$item["SETTINGS"]["HLBLOCK_ID"]],
                        "reference" => array(
                            "=this." . $item["FIELD_NAME"] => "ref.ID"
                        ),
                    );
                    $alias .= "Id";
                    $field = $item["FIELD_NAME"];
                    break;
                default:
                    // список возможных значений
                    $field = $item["FIELD_NAME"];
                    break;
            }
            $result[$alias] = $field;
        }

        return $result;
    }


    /**
     * Выясняем возможно ли удаление данного элемента
     * Для этого ищем ссылки в HL блоках на эту сущность.
     * Далее в этих блоках ищем записи со ссылкой на удаляемый элемент.
     * Если такие записи найдены, то запись не должна быть удалена
     * @param  int  $primaryKey
     * @return boolean
     * @todo Добавить обработку инфоблоков
     */
    protected function isDeletable($primaryKey)
    {
        $obj = new Element\UserField;
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
            $obj = new Element\HlElement(array(
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

    /**
     * Возвращает все HL блоки
     * @return array
     */
    private function getListBlocks(array $params = array())
    {
        $result = false;
        $objBlock = HL\HighloadBlockTable::getList();
        while ($blockEl = $objBlock->Fetch()) {
            $result[$blockEl["ID"]] = $blockEl;
        }
        return $result;
    }
}
