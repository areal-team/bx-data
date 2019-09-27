<?php

namespace Akop\Element;

use \Bitrix\Highloadblock as HL;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class HlBlock extends AbstractElement
{
    // protected $primaryKey = "ID";
    protected $fields = ["ID", "NAME", "TABLE_NAME"];

    public function getList(array $params = [])
    {
        parent::getList($params);
        $result = false;
        \CModule::includeModule('highloadblock');
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

        $objBlock = HL\HighloadBlockTable::getList($getListParams);
        while ($element = $objBlock->Fetch()) {
            $result[$element["ID"]] = $element;
        }
        return $result;
    }

    public function getEntityDataClass(array $filter)
    {
        if (empty($hlblock = $this->getRow(['filter' => $filter]))) {
            throw new \Exception("Не найден Highload block " . key($filter) . " = " . current($filter), 404);
        }

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $edc = (string) $entity->getDataClass();
        return new $edc;
    }
}
