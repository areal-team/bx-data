<?
namespace Akop;

class Security
{
	/**
	 * Сравнивает эталонный referer и реальный
	 * @param  string $urlEtalon эталонный referer
	 * @return boolean
	 */
    public function isRefererValid($urlEtalon)
    {
        $referer = substr($_SERVER["HTTP_REFERER"], strpos($_SERVER["HTTP_REFERER"], "//") + 2);
        return ( $referer == $_SERVER["HTTP_HOST"] . $urlEtalon );
    }

	/**
	 * Находится ли пользователь в нужной группе
	 * @param  int $groupId id группы
	 * @return boolean
	 */
    public function isUserGroupValid($groupId)
    {
    	$user = new \Akop\User;
    	return $user->isInGroup($groupId);
    }

	/**
	 * Проверяет запущен ли скрипт из веба или из консоли (крона)
	 * @return boolean
	 */
    public function isCLI()
    {
    	return ( php_sapi_name() == "cli" );
    }
}