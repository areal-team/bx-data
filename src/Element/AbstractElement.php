<?php
namespace Akop\Element;

/**
 * Базовый класс для элементов
 * В нем реализован основной функционал для работы с данными
 */
class AbstractElement implements ElementInterface
{
    use ParamTrait;

    protected $fieldsBase = [];
    protected $fields = ["ID"];
    protected $reversedFields = [];
    protected $compressedFields = [];
    protected $rename = [];
    protected $primaryKey = "ID";

    private $errorMesage = '';
    private $lastOperation = false;

    public function __construct()
    {
        $this->fields = $this->getMap();
        $this->reverseFields();
    }

    /**
     * Возвращает набор строк
     * @param $params array допустимы параметры: select, filter, limit, order
     * @return array
     */
    public function getList(array $params = [])
    {
        $this->startNewOperation('getList');
        // если передан параметр group, то данные select игнорируем
        if (isset($params["group"])) {
            $params["select"] = $params["group"];
        }
        $this->params = $params;
        $this->updateParams();
        return [];
    }

    /**
     * Возвращает одну строку из набора данных
     * @param $params array допустимы параметры: select, filter, limit, order
     * @return array
     */
    public function getRow(array $params = [])
    {
        $params["limit"] = 1;
        $result = $this->getList($params);
        // print_r($result);

        return is_array($result)
            ? current($result)
            : false;
    }

    /**
     * Добавляет элемент
     * @param $params array набор полей для записи в БД
     */
    public function add(array $params)
    {
        $this->startNewOperation('add');
        $params = $this->compressFields($params);
        $this->params = $this->getUpdatedParamsFromArray($params);
        return false;
    }

    /**
    * Удаление элемента должно быть реализовано в наследниках
    * @param $primaryKey
    */
    public function delete($primaryKey)
    {
        $this->startNewOperation('delete');
        if (!$this->isDeletable($primaryKey)) {
            $this->setErrorMessage("Удаление невозможно. Существуют зависимые объекты.");
            return false;
        }
        return true;
    }

    protected function isDeletable($primaryKey)
    {
        return true;
    }

    /**
    * Обновляет элемент
    * @param $primaryKey integer
    * @param $params array набор полей для записи в БД
    */
    public function update($primaryKey, array $params)
    {
        $this->startNewOperation('update');
        $params = $this->compressFields($params);
        $this->params = $this->getUpdatedParamsFromArray($params);
        return false;
    }

    /**
     * Обновляет данные или добавляет их
     * Если будет найдена строка по параметру $filter,
     * то данные обновляются
     * в противном случае данные добавляются
     * @param $filter array
     * @param $params array набор полей для записи в БД
     */
    public function upsert(array $filter, array $params)
    {
        $item = $this->getRow(array(
            "select" => array($this->primaryKey),
            "filter" => $filter,
        ));

        if ($item && $primaryKey = $item[$this->primaryKey]) {
            $this->update($primaryKey, $params);
            return $primaryKey;
        }
        return $this->add($params);
    }

    /**
     * Возвращает количество элементов в таблице
     */
    public function count(array $filter = [])
    {
        return count($this->getList(['select' => $this->primaryKey, 'filter' => $filter]));
    }

    public function getLastOperation()
    {
        return $this->lastOperation;
    }

    public function getMap()
    {
        return $this->fields;
    }


    public function getErrorMessage()
    {
        return $this->errorMesage;
    }

    public function setCompressedFields(array $fields = [])
    {
        return $this->compressedFields = $fields;
    }

    public function getCompressedFields()
    {
        return $this->compressedFields;
    }

    public function setFields(array $fields = [])
    {
        return $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }

    private function compressFields(array $fields)
    {
        foreach ($fields as $fieldName => $fieldValue) {
            $result[$fieldName] = $this->compressField($fieldName, $fieldValue);
        }
        return $result;
    }

    private function compressField($fieldName, $fieldValue)
    {
        return (in_array($fieldName, $this->compressedFields))
            ? bin2hex(gzcompress($fieldValue))
            : $fieldValue;
    }

/*
    private function uncompressFields(array $fields)
    {
        foreach ($fields as $fieldName => $fieldValue) {
            $result[$fieldName] = $this->uncompressField($fieldName, $fieldValue);
        }
        return $result;
    }
*/

    private function uncompressField($fieldName, $fieldValue)
    {
        return (in_array($fieldName, $this->compressedFields))
            ? gzuncompress(hex2bin($fieldValue))
            : $fieldValue;
    }

    protected function startNewOperation($operation)
    {
        $this->setErrorMessage('');
        $this->setLastOperation($operation);
    }

    protected function setLastOperation($operation)
    {
        $this->lastOperation = $operation;
    }

    protected function setErrorMessage($message)
    {
        $this->errorMesage = $message;
    }

    /**
     * Возвращает набор данных с переименованными полями
     * Если поля были сжаты, то разжимает их
     * @param  [array] $item набор данных
     * @return [array]
     */
    protected function getRenamed($item)
    {
        if (!empty($this->reversedFields)) {
            foreach ($item as $key => $value) {
                $fieldName = (isset($this->reversedFields[$key]))
                    ? $this->reversedFields[$key]
                    : $key;

                $result[$fieldName] = $this->uncompressField($fieldName, $value);
            }
        }
        return $result;
    }

    protected function updateValueForReverse($value)
    {
        return $value;
    }

    /**
     * Переворачиваем поля для удобства использования
     * @return void
     */
    private function reverseFields()
    {
        if (!empty($this->fields)) {
            foreach ($this->fields as $key => $value) {
                if (!is_array($this->fields[$key])) {
                    $this->reversedFields[$this->updateValueForReverse($value)] = $key;
                }
            }
        }
    }
}
