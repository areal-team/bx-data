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
    public function createCacheInstance($cacheId);
    public function saveCache($cache, $vars);
    public function clearCache();
    public function isCacheExists();
}
