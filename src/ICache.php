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
    public function _createCacheInstance($cacheId);
    public function _saveCache($cache, $vars);
    public function _clearCache();
    public function _isCacheExists();
}
