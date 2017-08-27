# Как добавить поля справочника в вывод

В классе, унаследованном от HlElement, необходимо переопределить функцию getRefFields следующим образом:

```PHP
protected function getRefFields()
{
    return [
        ['alias' => 'Name', 'fieldname' => 'UF_NAME'],
        ['alias' => 'Description', 'fieldname' => 'UF_DESCRIPTION'],
    ];
}
```

Если в highload блоке использкется более одного справочника, то можете воспользоваться конструкцией типа:


```PHP
protected function getRefFields($hlBlockId)
{
    switch () {
        case 1:
            return [
                ['alias' => 'Name', 'fieldname' => 'UF_NAME'],
                ['alias' => 'Description', 'fieldname' => 'UF_DESCRIPTION'],
            ];
          break;
        case 2:
            return [
                ['alias' => 'Name', 'fieldname' => 'UF_NAME'],
            ];
          break;
    }
}
```
