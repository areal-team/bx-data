<?php

namespace Akop\Element;

\CModule::includeModule('highloadblock');

use \Bitrix\Highloadblock as HL;
use \Akop\Element as Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class HlBlock extends BaseElement
{
    // protected $primaryKey = "ID";
    protected $fields = ["ID", "NAME", "TABLE_NAME"];


    public function getList(array $params = [])
    {
        parent::getList($params);
        $result = false;
        $objBlock = HL\HighloadBlockTable::getList($this->params);
        while ($element = $objBlock->Fetch()) {
            $result[$element["ID"]] = $element;
        }
        return $result;
    }
}
