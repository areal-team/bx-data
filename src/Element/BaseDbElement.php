<?php
namespace Akop\Element;

/**
 * Базовый класс для работы с данными из БД
 * Не наследоваться от данного класса!
 * @var $connectionName         Имя соединения
 * @var $scriptsAfterConnect    Скрипты выполняемве после коннекта
 * @author Андрей Копылов aakopylov@mail.ru
 */
class BaseDbElement extends AbstractElement
{
    protected $connectionName = "";
    protected $scriptsAfterConnect = [];
    protected $fieldsBase = [
        "id" => "id",
    ];

    public function __construct()
    {
        $this->connection = \Bitrix\Main\Application::getConnection($this->connectionName);
        if (!empty($this->scriptsAfterConnect)) {
            foreach ($this->scriptsAfterConnect as $script) {
                $this->connection->queryExecute($script);
            }
        }
        parent::__construct();
        $this->sqlHelper = $this->connection->getSqlHelper();
    }

}
