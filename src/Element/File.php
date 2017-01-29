<?php

namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class File extends DbElement
{
    protected $tableName = "b_file";

    public function getMap()
    {
        /*
        Field,Type,Null,Key,Default,Extra
        ID,int(18),NO,PRI,NULL,auto_increment
        TIMESTAMP_X,timestamp,NO,,CURRENT_TIMESTAMP,"on update CURRENT_TIMESTAMP"
        MODULE_ID,varchar(50),YES,,NULL,
        HEIGHT,int(18),YES,,NULL,
        WIDTH,int(18),YES,,NULL,
        FILE_SIZE,bigint(20),YES,,NULL,
        CONTENT_TYPE,varchar(255),YES,,IMAGE,
        SUBDIR,varchar(255),YES,,NULL,
        FILE_NAME,varchar(255),NO,,NULL,
        ORIGINAL_NAME,varchar(255),YES,,NULL,
        DESCRIPTION,varchar(255),YES,,NULL,
        HANDLER_ID,varchar(50),YES,,NULL,
        EXTERNAL_ID,varchar(50),YES,MUL,NULL,
*/
        return [
            "id" => "ID",
            "timestamp_x" => "TIMESTAMP_X",
            "height" => "HEIGHT",
            "width" => "WIDTH",
            "file_size" => "FILE_SIZE",
            "content_type" => "CONTENT_TYPE",
            "subdir" => "SUBDIR",
            "file_name" => "FILE_NAME",
            "original_name" => "ORIGINAL_NAME",
            // "external_id" => "EXTERNAL_ID",
        ];
    }
}
