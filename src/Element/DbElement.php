<?php

namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class DbElement extends BaseElement
{
    protected $tableName = "";
    protected $fieldsBase = [
        "id" => "id",
    ];


    public function __construct()
    {
        global $DB;
        $this->db = $DB;
        parent::__construct();
        return $this;
    }


    public function getList(array $params = array())
    {
        parent::getList($params);
       /*
        $res = $this->entityDC->getList($this->params);
        while ($item = $res->Fetch()) {
            $key = (isset($item["ID"]))
                    ? $item["ID"]
                    : count($result);

            $result[$key] = $this->getRenamed($item);
        }*/



        return $result;
    }


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
}
