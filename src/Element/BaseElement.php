<?php
namespace Akop\Element;

/**
 * Базовый класс для элементов
 * В нем реализован основной функционал для работы с данными
 */
class BaseElement implements IElement
{
    private static $filterPrefixes = [
        1 => ["=", "%", "?", ">", "<", "!", "@"],
        2 => ["!=", "!%", "><", ">=", "<=", "=%", "%=", "!@"],
        3 => ["!><", "!=%", "!%="],
    ];

    protected $fieldsBase = [];
    protected $fields = [];
    protected $reversedFields = [];
    protected $compressedFields = [];
    protected $rename = [];
    protected $primaryKey = "ID";
    protected $params = [];

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
        // если передан параметр group, то данные select игнорируем
        if (isset($params["group"])) {
            $params["select"] = $params["group"];
        }
        $this->params = $params;
        $this->updateParams();
        $this->setLastOperation('');
    }

    /**
     * Возвращает одну строку
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
        $params = $this->compressFields($params);
    }

    /**
    * Удаляет элемент
    * @param $primaryKey
    */
    public function delete($primaryKey)
    {
    }

    /**
    * Обновляет элемент
    * @param $primaryKey integer
    * @param $params array набор полей для записи в БД
    */
    public function update($primaryKey, array $params)
    {
        $params = $this->compressFields($params);
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

    public function compressFields(array $fields)
    {
        foreach ($fields as $fieldName => $fieldValue) {
            $result[$fieldName] = $this->compress($fieldName, $fieldValue);
        }
        return $result;
    }

    protected function compress($fieldName, $fieldValue)
    {
        return (in_array($fieldName, $this->compressedFields))
            ? bin2hex(gzcompress($fieldValue))
            : $fieldValue;
    }

    public function uncompressFields(array $fields)
    {
        foreach ($fields as $fieldName => $fieldValue) {
            $result[$fieldName] = $this->uncompress($fieldName, $fieldValue);
        }
        return $result;
    }

    protected function uncompress($fieldName, $fieldValue)
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

    protected function isDeletable($primaryKey)
    {
        return true;
    }

    /**
     * Обновляет параметры для функции getList
     * переименовывает поля, добавляет секцию runtime,
     * устанавливает доп фильтр к выборке
     * @return [type] [description]
     */
    protected function updateParams()
    {
        if (empty($this->params)) {
            $this->params = [];
        }
        $this->updateParamsFilter();
        $this->updateParamsSelect();
        $this->updateParamsOrder();
        $this->updateParamsGroup();
    }

    /**
     * Обновление параметров для выборки
     * @return void
     */
    protected function updateParamsSelect()
    {
        if (isset($this->params["runtime"])) {
            $this->params["runtime"] = [];
        }

        if (empty($this->params["select"])) {
            $this->params["select"] = array_keys($this->fields);
        }
        // \Akop\Util::pre([$this->params["select"], $this->fields], 'BaseElement updateParamsSelect fields');
        if (!empty($this->fields)) {
            $result = [];
            foreach ($this->params["select"] as $value) {
                if (!isset($this->fields[$value])) {
                    $result[] = $value;
                    continue;
                }

                if (!is_array($this->fields[$value])) {
                    $result[] = $this->fields[$value];
                    continue;
                }

                $result[$value] = $value . "_." . $this->fields[$value]["name"];
                $this->params["runtime"][$value . "_"] = $this->fields[$value];
            }
            $this->params["select"] = $result;
        }
        // \Akop\Util::pre([$this->params["select"], $this->params["runtime"]], 'BaseElement updateParamsSelect select');
    }

    /**
     * Обновление параметров для фильтрации
     * @return void
     */
    protected function updateParamsFilter()
    {
        $this->updateParamsBase("filter");
    }

    /**
     * Обновление параметров для сортировки
     * @return void
     */
    protected function updateParamsOrder()
    {
        $this->updateParamsBase("order");
    }

    /**
     * Обновление параметров для сортировки
     * @return void
     */
    protected function updateParamsGroup()
    {
        if ((!empty($this->params["group"])) && (!empty($this->fields))) {
            $result = [];
            foreach ($this->params["group"] as $value) {
                $result[] = ((!isset($this->fields[$value]) || is_array($this->fields[$value]))
                    ? $value
                    : $this->fields[$value]
                );
            }
            $this->params["group"] = $result;
        }
    }

    protected function updateParamsBase($paramName)
    {
        $params = (!empty($this->params[$paramName])) ? $this->params[$paramName] : [];
        $this->params[$paramName] = $this->getUpdatedParamsFromArray($params);
    }

    protected function getUpdatedParamsFromArray(array $params)
    {
        $result = [];
        if ((!empty($params)) && (!empty($this->fields))) {
            foreach ($params as $key => $value) {
                $fieldName = $this->getCleanFieldName($key);
                $resultKey = ($fieldName && !is_array($this->fields[$fieldName["name"]])
                    ? $fieldName["prefix"] . $this->fields[$fieldName["name"]]
                    : $key
                );
                $result[$resultKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Возвращает очищенное имя поля и префикс
     *   или false если поле не найдено
     * Используется для фильтра
     * @param $fieldName
     * @return array | false
     */
    public function getCleanFieldName($fieldName)
    {
        $result = false;
        // print_r([$this->fields, $fieldName]);

        if ($result = $this->getFieldNameAndPrefix($fieldName, "")) {
            return $result;
        }

        for ($prefixLength = 3; $prefixLength > 0; $prefixLength--) {
            if ($result = $this->getCleanFieldNameWithPrefix($fieldName, $prefixLength)) {
                break;
            }
        }

        return $result;
    }

    private function getCleanFieldNameWithPrefix($fieldName, $prefixLength)
    {
        $prefix = substr($fieldName, 0, $prefixLength);
        $cleanFieldName = substr($fieldName, $prefixLength);
        if (in_array($prefix, self::$filterPrefixes[$prefixLength])) {
            return $this->getFieldNameAndPrefix($cleanFieldName, $prefix);
        }
        return false;
    }

    private function getFieldNameAndPrefix($fieldName, $prefix)
    {
        return ($this->isFieldExists($fieldName)
            ? ["name" => $fieldName, "prefix" => $prefix]
            : false
        );
    }

    private function isFieldExists($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Возвращает набор данных с переименованными полями
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

                $result[$fieldName] = $this->uncompress($fieldName, $value);
            }
        }
        return $result;
    }


    protected function setLastOperation($operation)
    {
        $this->lastOperation = $operation;
    }

    protected function setErrorMessage($message)
    {
        $this->errorMesage = $message;
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


    /**
     * Объединение массивов полей у базового класса
     * и его наследников
     * Этот массив в дальнейшем используется для операций с БД
     * @return [type] [description]
     * @deprecated Необходимо использовать getMap
     */
     /*
    private function mergeFields()
    {
        $this->fields = array_merge($this->fieldsBase, $this->fields);
    }
    */
}
