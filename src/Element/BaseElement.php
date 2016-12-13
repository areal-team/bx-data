<?php
namespace Akop\Element;

use \Bitrix\Main\Data\cache;

class BaseElement implements IElement
{
    /*
    static $triple_char = array(
        "!><"=>"NB", //not between
        "!=%"=>"NM", //not Identical by like
        "!%="=>"NM", //not Identical by like
    );

    static $double_char = array(
        "!="=>"NI", //not Identical
        "!%"=>"NS", //not substring
        "><"=>"B",  //between
        ">="=>"GE", //greater or equal
        "<="=>"LE", //less or equal
        "=%"=>"M", //Identical by like
        "%="=>"M", //Identical by like
        "!@"=>"NIN", //Identical by like
    );
*/
    private static $single_char = [
        "="=>"I", //Identical
        "%"=>"S", //substring
        "?"=>"?", //logical
        ">"=>"G", //greater
        "<"=>"L", //less
        "!"=>"N", // not field LIKE val
        "@"=>"IN" // IN (new SqlExpression)
    ];

    protected $cachePeriod = 3600;
    protected $fieldsBase = [];
    protected $fields = [];
    protected $reversedFields = [];
    protected $compressedFields = [];
    protected $rename = [];
    protected $primaryKey = "ID";
    protected $params = [];
    protected $arCache = [];

    private $_errorMesage = '';
    private $_lastOperation = false;

    public function __construct()
    {
        $this->fields = $this->getMap();
        $this->reverseFields();
    }


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

    public function getRow(array $params = [])
    {
        $params["limit"] = 1;
        $result = $this->getList($params);

        return is_array($result)
            ? current($result)
            : false;
    }

    public function add(array $params)
    {
        $this->beforeAdd();
        $params = $this->compressFields($params);
        $this->afterAdd();
    }

    public function delete($id)
    {
        $this->afterDelete();
    }

    public function update($id, array $params)
    {
        $this->beforeUpdate();
        $params = $this->compressFields($params);
        $this->afterUpdate();
    }

    /* Добавляем данные или обновляем их */
    public function upsert(array $filter, array $params)
    {
        $item = $this->getRow(array(
            "select" => array($this->primaryKey),
            "filter" => $filter,
        ));

        if ($item) {
            if ($id = $item[$this->primaryKey]) {
                $this->update($id, $params);
            }
        } else {
            $id = $this->add($params);
        }
        return $id;
    }

    public function getLastOperation()
    {
        return $this->_lastOperation;
    }

    public function getMap()
    {
        return $this->fields;
    }


    public function getErrorMessage()
    {
        return $this->_errorMesage;
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

    public function compress($fieldName, $fieldValue)
    {
        // \Akop\Util::pre([in_array($fieldName, $this->compressedFields), $fieldValue, $fieldName], 'BaseElement compress');
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

    public function uncompress($fieldName, $fieldValue)
    {
        return (in_array($fieldName, $this->compressedFields))
            ? gzuncompress(hex2bin($fieldValue))
            : $fieldValue;
    }

    protected function beforeAdd()
    {
        $this->setErrorMessage('');
    }

    protected function beforeDelete()
    {
        $this->setErrorMessage('');
    }

    protected function beforeUpdate()
    {
        $this->setErrorMessage('');
    }

    protected function afterAdd()
    {
        $this->setLastOperation('add');
        $this->_clearCache();
    }

    protected function afterDelete()
    {
        $this->setLastOperation('delete');
        $this->_clearCache();
    }

    protected function afterUpdate()
    {
        $this->setLastOperation('update');
        $this->_clearCache();
    }

    protected function isDeletable($id)
    {
        return true;
    }

    /**
     * Обновляет параметры для функции getList
     * переименовывает поля, добавляет секцию runtime, устанавливает доп фильтр к выборке
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
        if (empty($this->params["select"])) {
            $this->params["select"] = array_keys($this->fields);
        }

        if (!empty($this->fields)) {
            $result = [];
            foreach ($this->params["select"] as $key => $value) {
                if (isset($this->fields[$value])) {
                    if (!is_array($this->fields[$value])) {
                        $result[] = $this->fields[$value];
                    } else {
                        $result[$value] = $value . "_." . $this->fields[$value]["name"];
                        $this->params["runtime"][$value . "_"] = $this->fields[$value];
                    }
                } else {
                    $result[] = $value;
                }
            }
            $this->params["select"] = $result;
        }
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
            foreach ($this->params["group"] as $key => $value) {
                if (isset($this->fields[$value])) {
                    if (!is_array($this->fields[$value])) {
                        $result[] = $this->fields[$value];
                    } else {
                        $result[] = $value;
                    }
                } else {
                    $result[] = $value;
                }
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
                if ($fieldName = $this->getCleanFieldName($key)) {
                    if (!is_array($this->fields[$fieldName["name"]])) {
                        $result[$fieldName["prefix"] . $this->fields[$fieldName["name"]]] = $value;
                    } else {
                        $result[$key] = $value;
                    }
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    private function getCleanFieldName($key)
    {
        $prefix = substr($key, 0, 1);
        $result = ((in_array($prefix, array("!", ">", "<", "=", "%")))
            ? array("name" => substr($key, 1), "prefix" => $prefix)
            : ((isset($this->fields[$key]))
                ? array("name" => $key, "prefix" => "")
                : false
            )
        );

        return $result;
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
        $this->_lastOperation = $operation;
    }

    protected function setErrorMessage($message)
    {
        $this->_errorMesage = $message;
    }

    /* Создаем Instance кэша при установленном периоде кэширования и наличии в параметрах пути и ид кэша */
    protected function _createCacheInstance($cacheId)
    {
        $this->arCache["exists"] = false;
        $this->arCache["id"] = $cacheId;
        if ($this->arCache["cachePeriod"] > 0) {
            $cache = \Bitrix\Main\Data\cache::createInstance();
            $this->arCache["exists"] = $cache->initCache(
                $this->arCache["cachePeriod"],
                $cacheId,
                $this->arCache["path"]
            );

            $result = $cache;
        } else {
            $result = false;
        }
        return $result;
    }

    /* сохраняем данные в кэш при установленном периоде кэширования */
    protected function _saveCache($cache, $vars)
    {
        if (($this->arCache["cachePeriod"] > 0) && $cache && $vars) {
            $cache->startDataCache();
            $cache->endDataCache($vars);
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    protected function _clearCache()
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->cleanDir(
            $this->arCache["path"]
        );
    }

    protected function _isCacheExists()
    {
        if (isset($this->arCache)
            && is_array($this->arCache)
            && isset($this->arCache["exists"])
        ) {
            $result = $this->arCache["exists"];
        } else {
            $result = false;
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


    /**
     * Объединение массивов полей у базового класса и его наследников
     * Этот массив в дальнейшем используется для операций с БД
     * @return [type] [description]
     * @deprecated Необходимо использовать getMap
     */
    private function mergeFields()
    {
        $this->fields = array_merge($this->fieldsBase, $this->fields);
    }
}
