<?
namespace Akop;

class User extends \CUser
{

    public function AuthByLogin($login)
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
