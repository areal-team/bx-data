<?php
namespace Akop\Element;

class User extends DbElement
{
    protected $tableName = 'b_user';

    /**
    * Только админы могут получать доступ к таблице b_user
    */
    public function __construct()
    {
        if ($this->isAdmin()) {
            parent::__construct();
        }
    }

    private function isAdmin()
    {
        $user = new \Akop\User;
        if (!$user->isInGroup(1)) {
            throw new \Exception("Acces Denied for table b_user", 403);
        }
        return true;
    }
}
