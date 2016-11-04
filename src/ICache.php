<?php

namespace Akop;

/**
 * Интерфейс кэша
 * @author: Андрей Копылов
 * @mail: aakopylov@mail.ru,
 * @skype: andrew.kopylov.74
 */
interface ICache
{
	function _createCacheInstance($cacheId);
	function _saveCache($cache, $vars);
	function _clearCache();
	function _isCacheExists();
}