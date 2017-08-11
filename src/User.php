<?php
namespace Akop;

class User extends \CUser
{

    public function getList1(array $params)
    {
        $list = self::GetList(
            ($by="NAME"),
            ($order="asc"),
            $params['filter']
        );
        $result = [];
        while ($user = $list->Fetch) {
            $result[] = $user;
        }
        return $result;
    }

    public function authByLogin($login)
    {
        $user = $this->GetByLogin($login)->fetch();
        return $this->Authorize($user["ID"]);
    }

    public function isInGroup($groupId)
    {
        return in_array($this->getCurrent(), $this->getGroups($userId));
    }

    public function getGroups($userId)
    {
        return $this->GetUserGroup($userId);
    }

    public function getCurrent()
    {
        return $this->GetID();
    }

    public function getFullName($userId)
    {
        $user = $this->GetByID($userId)->Fetch();
        return $user["NAME"].($user["NAME"] == '' || $user["LAST_NAME"] == ''? "":" ").$user["LAST_NAME"];
    }
}
