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
        \Akop\Util::pre($this->params, 'getList params');
        // \Akop\Util::pre($this->sqlHelper, 'getList this->sqlHelper');
        $querySet = new QuerySet($this->tableName);
        $querySet->addSelect($this->params['select']);
        $querySet->addFilter($this->params['filter']);
        $querySet->addOrder($this->params['order']);
        $querySet->setLimit($this->params['limit']);
        \Akop\Util::pre($querySet->toSQL(), '$querySet->toSQL');

        $list = $this->connection->query($querySet->toSQL());
        while ($item = $list->fetch()) {
            $result[] = $item;
        }
        return $result;
    }

/*
    public function add(array $params)
    {
        $fields = '';
        $countFields = 1;
        foreach ($params as $key => $value) {
            $fields.= "`".$key."` = '".$value."'";
            if($countFields < sizeof($params)){
                $fields.=',';
            }
            $countFields ++;
        }
        $query = "INSERT INTO ".$this->tableName." SET ".$fields;
        $result = $this->db->Query($query);
        return $result;
    }
    public function delete($primaryKey)
    {

    }


    public function update($primaryKey, array $params)
    {

    }


    public function getMap()
    {

    }
    */
}
