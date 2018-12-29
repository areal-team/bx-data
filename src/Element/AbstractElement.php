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
    protected $dateFormat = 'd.m.Y';
    protected $dateTimeFormat = 'd.m.Y H:i:s';
    protected $dates = [];
    protected $fields = ["ID"];
    protected $reversedFields = [];
    protected $compressedFields = [];
    protected $primaryKey = "ID";
    protected $isAssoc = true;
    protected $fieldsStripTags = [];
    protected $fieldsIgnore = [];

    private $errorMesage = '';
    private $lastOperation = false;

    // protected $translitParams = [
    //     "max_len" => "100",
    //     "change_case" => "L",
    //     "replace_space" => "-",
    //     "replace_other" => "-",
    //     "delete_repeat_replace" => "true",
    //     "use_google" => "false",
    // ];

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
        /* По умолчанию возвращаем ассоциативный массив */
        $this->isAssoc = (isset($params["isAssoc"])
            ? $params["isAssoc"]
            : true
        );
        unset($params['isAssoc']);

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

        $primaryKey = null;
        if ($item[$this->primaryKey]) {
            $primaryKey = $item[$this->primaryKey];
        }
        if ($item[strtolower($this->primaryKey)]) {
            $primaryKey = $item[strtolower($this->primaryKey)];
        }

        if ($item && $primaryKey) {
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
     * Возвращает обработанный набор данных
     *  - Переименовывает поля
     *  - Если поля были сжаты, то разжимает их
     *  - Если поля указаны в массиве дат, то значение преобразуются в строку
     * @param  [array] $item набор данных
     * @return [array]
     */
    protected function getProcessed($item)
    {
        if (!empty($this->reversedFields)) {
            foreach ($item as $key => $value) {
                $fieldName = (isset($this->reversedFields[$key]))
                    ? $this->reversedFields[$key]
                    : $key;

                $value = $this->uncompressField($fieldName, $value);
                $value = $this->convertDateFromDB($fieldName, $value);
                $result[$fieldName] = $this->stripTags($fieldName, $value);
            }
        }
        return $result;
    }

    /**
     * Удалят html теги
     */
    private function stripTags($fieldName, $value)
    {
        return (!empty($value) && in_array($fieldName, $this->fieldsStripTags))
            ? strip_tags($value)
            : $value;
    }

    /**
     * Если поле указано в массиве дат, то возвращает вместо объекта строку
     * Для того, чтобы избежать это преобразование достаточно не заполнять массив дат
     */
    private function convertDateFromDB($fieldName, $value)
    {
        // \Akop\Util::pre([$fieldName, $value, $this->dates, in_array($fieldName, $this->dates)], 'convertDateFromDB');
        return (!empty($value) && in_array($fieldName, $this->dates))
            ? $value->toString()
            : $value;
    }

    /** 
     * Преобразует строку в объект дата, пригодный для сохранения в БД
     */
    private function convertDateToDB($fieldName, $value)
    {
        if (in_array($fieldName, $this->dates) && !empty($value)) {
            if ($this->isTimeProbably($value)) {
                $dt = new \Bitrix\Main\Type\DateTime($value, $this->dateTimeFormat);
                return $dt->format('Y-m-d H:i:s');
            }
            $dt = new \Bitrix\Main\Type\DateTime($value, $this->dateFormat);
            return $dt->format('Y-m-d');
        }
        return $value;
    }

    /**
     * Является ли строка временем (предположительно)
     * так как используется очень ограниченно, то такое условие вполне достаточно
     * При особых DateFormat следует переопределить функцию
     */
    protected function isTimeProbably($value)
    {
        return (strlen($value) > 10);
    }

    protected function updateValueForReverse($value)
    {
        return $value;
    }

    public function translit($value)
    {
        return \CUtil::translit($value, "ru" , $this->translitParams);
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
    
    protected function getLastId()
    {
        global $DB;
        return $DB->LastID();
    }    
}
