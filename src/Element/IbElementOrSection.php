<?php
namespace Akop\Element;

/**
 * Класс частично реализует общий функционал по работе с разделами и элементами
 * Для наследования использовать классы
 *  \Akop\Element\Section - для разделов
 *  \Akop\Element\Element - для элементов
 */
class IbElementOrSection extends AbstractElement
{
    protected $iblockCode = false;
    protected $iblockId = false;

    public function __construct()
    {
        \CModule::IncludeModule("iblock");
        $this->setIblockId();
        parent::__construct();
    }

    /**
     * Функция добавляет и преобразовывает параметры
     * @return void
     */
    public function getList(array $params = array())
    {
        $params["filter"]["IBLOCK_ID"] = $this->iblockId;
        /* Оставляем возможность выбрать неактивные элементы */
        if (!isset($params["filter"]["!ACTIVE"])) {
            $params["filter"]["ACTIVE"] = "Y";
        }

        if (!empty($params["limit"])) {
            $params["limit"] = $this->getLimit($params["limit"]);
        }
        parent::getList($params);
        return false;
    }

    /**
     * Функция добавляет параметр IBLOCK_ID
     * @return void
     */
    public function add(array $params)
    {
        if (empty($params["IBLOCK_ID"])) {
            $params["IBLOCK_ID"] = $this->iblockId;
        }
        parent::add($params);
        return false;
    }

    /**
     * Устанавливает id инфоблока по переданным параметрам
     * @param array $params
     * @return void
     */
    protected function setIblockId($params = [])
    {
        if (!empty($params['iblockCode'])) {
            $this->iblockCode = $params['iblockCode'];
        }

        $this->iblockId = ( ( !empty($this->iblockCode) )
            ? $this->getIblockIdByCode($this->iblockCode)
            : $params["iblockId"]
        );
    }

    /**
     * Возвращает ID инфоблока по его коду
     * @return int
     */
    protected function getIblockIdByCode($iblockCode)
    {
        $result = false;
        $iblock = \CIBlock::GetList(false, array('CODE' => $iblockCode));
        if ($res = $iblock->Fetch()) {
            $result = $res['ID'];
        }
        return $result;
    }

    /**
     * Обработка limit для getList() битрикса (приходит в таком же формате как и в DbElement)
     * @param $params
     * @return array
     */
    private function getLimit($params): array
    {
        if (is_array($params)) {
            if (count($params) == 2) {
                $params = array(
                    "iNumPage" => (int)(($params[0] + $params[1]) / $params[1]),
                    "nPageSize" => $params[1]
                );
            }
        } else {
            $params = array("nTopCount" => $params);
        }
        return $params;
    }
}
