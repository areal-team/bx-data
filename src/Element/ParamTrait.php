<?php
namespace Akop\Element;

/**
 * Базовый класс для элементов
 * В нем реализован основной функционал для работы с данными
 */
trait ParamTrait
{
    private static $filterPrefixes = [
        1 => ["=", "%", "?", ">", "<", "!", "@"],
        2 => ["!=", "!%", "><", ">=", "<=", "=%", "%=", "!@"],
        3 => ["!><", "!=%", "!%="],
    ];
    protected $params = [];

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

        $this->addPrimaryKeyToSelect();
        $this->addCountToSelect();
        /*
        \Akop\Util::pre(
            [
                $this->params["select"],
                $this->params["runtime"]
            ],
            'BaseElement updateParamsSelect select');
        */
    }

    private function addPrimaryKeyToSelect()
    {
        if (!empty($this->params["select"])
            && !in_array($this->primaryKey, $this->params["select"])
            && empty($this->params["group"])
        ) {
            $this->params["select"][] = $this->primaryKey;
        }
    }

    private function addCountToSelect()
    {
        if (!empty($this->params["group"])) {
            $this->params["select"][] = cnt;
            $this->params["runtime"]['cnt'] = new \Bitrix\Main\Entity\ExpressionField('cnt', 'COUNT(*)');
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
}
