<?php
namespace Akop\Element;

/**
 * Базовый класс для элементов
 * В нем реализован основной функционал для работы с данными
 */
class BaseElement extends AbstractElement
{
    /**
    * Удаляет элемент
    * @param $primaryKey
    */
    public function delete($primaryKey)
    {
        return false;
    }

    protected function updateImplement($primaryKey, array $params)
    {
        return $primaryKey;
    }

    protected function addImplement(array $params)
    {
        return true;
    }

    protected function isDeletable($primaryKey)
    {
        return true;
    }
}
