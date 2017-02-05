<?php
namespace Akop\Element;

class FieldPrefix
{
    private $fields;
    private static $filterPrefixes = [
        1 => ["=", "%", "?", ">", "<", "!", "@"],
        2 => ["!=", "!%", "><", ">=", "<=", "=%", "%=", "!@"],
        3 => ["!><", "!=%", "!%="],
    ];

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getFieldsPrefixes(array $fields)
    {
        if (empty($this->fields)) {
            return $this->getFieldsPrefixesForEmpty($fields);
        }
        return $this->getFieldsPrefixesForNonEmpty($fields);
    }

    private function getFieldsPrefixesForNonEmpty(array $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            $res = $this->getFieldWithPrefix($field);
            // \Akop\Util::pre([$this->fields, $field, $res], 'getFieldsWithPrefixes');
            $result[$res['name']] = $res['prefix'];
        }
        return $result;
    }

    private function getFieldsPrefixesForEmpty(array $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = '';
        }
        return $result;
    }

    public function getFieldWithPrefix($field)
    {
        // \Akop\Util::pre([$this->fields, $field], 'getFieldWithPrefix');
        return $this->getCleanFieldName($field);
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
        return in_array($fieldName, $this->fields);
    }
}
