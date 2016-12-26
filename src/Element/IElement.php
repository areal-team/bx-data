<?php

namespace Akop\Element;

/**
 * Интерфейс работы с элементами
 * @author: Андрей Копылов
 * @mail: aakopylov@mail.ru,
 */
interface IElement
{
    public function getList(array $params = array());
    public function getRow(array $params = array());
    public function add(array $params);
    public function delete($primaryKey);
    public function update($primaryKey, array $params);
    public function upsert(array $filter, array $params);
}
