<?php
// bitrix/modules/main/include.php with no authorizing and Agents execution
define("NOT_CHECK_PERMISSIONS", true);
define("NO_AGENT_CHECK", true);
define("LANG", "ru");
define("NO_KEEP_STATISTIC", true);
define("BX_BUFFER_USED", true);
// define("BX_CLUSTER_GROUP", 2);
// $GLOBALS["DBType"] = 'mysql';
$_SERVER["DOCUMENT_ROOT"] = __DIR__ . '/../../../..';
// require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
//
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/classes/Akop/autoload.php');
$loader = new Psr4AutoloaderClass();
$loader->register();
$loader->addNamespace('Akop', $_SERVER["DOCUMENT_ROOT"] . "/local/classes/Akop/");

while (ob_get_level()) {
    ob_end_flush();
}

function initBitrixCore()
{
	// manual saving of DB resource
    global $DB;
    CModule::includeModule('iblock');

    $app = \Bitrix\Main\Application::getInstance();
    $con = $app->getConnection();
    $DB->db_Conn = $con->getResource();


    // "authorizing" as admin
    $_SESSION["SESS_AUTH"]["USER_ID"] = 1;
}
