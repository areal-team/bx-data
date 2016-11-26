<?php
namespace Akop;

class CachedEntity implements ICache
{

    protected $arCache = array();

    public function __construct($params = array())
    {
        $this->arCache = $params;
    }

    /* Создаем Instance кэша при установленном периоде кэширования и наличии в параметрах пути и ид кэша */
    public function _createCacheInstance($cacheId)
    {
        $this->arCache["exists"] = false;
        $this->arCache["id"] = $cacheId;
        if ($this->arCache["cachePeriod"] > 0) {
            $cache = \Bitrix\Main\Data\cache::createInstance();
            $this->arCache["exists"] = $cache->initCache(
                $this->arCache["cachePeriod"],
                $cacheId,
                $this->arCache["path"]
            );

            $result = $cache;
        } else {
            $result = false;
        }
        return $result;
    }

    /* сохраняем данные в кэш при установленном периоде кэширования */
    public function _saveCache($cache, $vars)
    {
        if (( $this->arCache["cachePeriod"] > 0 ) && $cache && $vars) {
            $cache->startDataCache();
            $cache->endDataCache($vars);
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public function _clearCache()
    {
        $cache = \Bitrix\Main\Data\cache::createInstance();
        $cache::clearCache(false, $this->arCache["path"]);
    }

    public function _isCacheExists()
    {
        if (isset($this->arCache)
            && is_array($this->arCache)
            && isset($this->arCache["exists"])
        ) {
            $result = $this->arCache["exists"];
        } else {
            $result = false;
        }

        return $result;
    }
}
