<?php
namespace Akop\Element;

/**
 * Класс для работы с данными из SQL запроса
 * Необходимо наследовать класс с указанием 
 * @var $sql                    Выполняемый SQL
 * @var $connectionName         Имя соединения
 * @var $scriptsAfterConnect    Скрипты выполняемве после коннекта
 * @author Андрей Копылов aakopylov@mail.ru
 */
class DbQuery extends BaseDbElement
{
    protected $sql = null;

    public function getList(array $params = array())
    {
        parent::getList($params);
        $list = $this->connection->query($this->sql);
        while ($item = $list->fetch()) {
            if (empty($params) || empty($params['filter'])) {
                $result[] = $this->getProcessed($item);
                continue;
            } 
            if ($this->isRowMatchesToFilter($item, $params)) {
                $result[] = $this->getProcessed($item);
            }
        }
        return $result;
    }
    
    /**
     * Проверяет соответствие записи фильтру
     * Реализована только проверка равенства
     */
    private function isRowMatchesToFilter($item, $params)
    {
        if (empty($params['filter'])) {
            return true;
        }
        foreach ($params['filter'] as $filterKey => $filterValue) {
            if ($item[$filterKey] != $filterValue) {
                return false;
            }
        }
        return true;
    }

    protected function updateParams()
    {

    }    
}
