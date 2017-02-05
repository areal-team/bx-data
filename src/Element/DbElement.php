<?php
namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class DbElement extends AbstractElement
{
    protected $tableName = "";
    protected $fieldsBase = [
        "id" => "id",
    ];


    public function __construct()
    {
        // global $DB;
        // $this->db = $DB;
        parent::__construct();
        $this->connection = \Bitrix\Main\Application::getConnection();
        $this->sqlHelper = $this->connection->getSqlHelper();
        // return $this;
    }


    public function getList(array $params = array())
    {
        parent::getList($params);
        //\Akop\Util::pre($this->params, 'getList params');
        // \Akop\Util::pre($this->sqlHelper, 'getList this->sqlHelper');
        $querySet = new QuerySet($this->tableName);
        $querySet->addSelect($this->params['select']);
        $querySet->addFilter($this->params['filter']);
        $querySet->addOrder($this->params['order']);
        $querySet->setLimit($this->params['limit']);
       // \Akop\Util::pre($querySet->getSelectSQL(), '$querySet->getSelectSQL');

        $list = $this->connection->query($querySet->getSelectSQL());
        while ($item = $list->fetch()) {
            $result[] = $item;
        }
        return $result;
    }


    public function add(array $params)
    {
        $querySet = new QuerySet($this->tableName);
        $this->connection->queryExecute($querySet->getAddSQL($params));
        return ($this->connection->getAffectedRowsCount() > 0);
    }

    public function delete($primaryKey)
    {
        $querySet = new QuerySet($this->tableName, $this->primaryKey);
        $this->connection->queryExecute($querySet->getDeleteSQL($primaryKey));
        return ($this->connection->getAffectedRowsCount() > 0);
    }

    public function update($primaryKey, array $params)
    {
        $querySet = new QuerySet($this->tableName, $this->primaryKey);
        $this->connection->queryExecute($querySet->getUpdateSQL($primaryKey, $params));
        return ($this->connection->getAffectedRowsCount() > 0);
    }
}
