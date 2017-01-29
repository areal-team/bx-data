<?php

namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class File extends DbElement
{
    protected $tableName = "b_file";

    public function getMap()
    {
        

    }
}
