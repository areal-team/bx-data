<?
namespace Akop\Meta;

\CModule::includeModule('highloadblock');

use Bitrix\Highloadblock as HLB;

/**
 * @author Андрей Копылов aakopylov@mail.ru
 * @todo большинство констант нужно переделать на параметры (это клиентские данные)
 */
class Hl extends \Gb\Element\BaseElement
{

    const
    	CURRENT_VERSION = 1,
    	TABLE_PREFIX = "GbAuto",
    	NAMESPACE_FOR_SAVE = "Akop\\Catalog",
        PATH_TO_CLASSES = "/local/classes/",
        PATH_TO_META = "/local/meta/";

	public
		$meta = array(),
		$allDependencies = array(),
		$dependencies = array();

	/**
	 * Возвращает все HL блоки
	 * @return array
	 */
	public function getList(array $params = array())
	{
		$result = false;
		$objBlock = HLB\HighloadBlockTable::getList($params);
		while ($blockEl = $objBlock->Fetch()) {
			$result[$blockEl["ID"]] = $blockEl;
		}
		return $result;
	}

	/**
	 * Возвращает метаданные по всем HL блокам
	 * @return array
	 */
	public function getMetaAll(array $params = array())
	{
		$list = $this->getList();
		foreach ($list as $key => $value) {
			$result[$value["NAME"]] = $this->getMetaData($value["NAME"]);
		}

		return $result;
	}

	/**
	 * Возвращает метаданные HL блока по его имени
	 * @param string $blockName Имя блока
	 * @return array
	 */
	public function getMetaData($blockName) {
		$result = false;
		if ( $block = $this->_getBlockInfoByName($blockName) ) {
			$this->meta = array();
			$this->dependencies = array();
			$this->_getMetaData($block["NAME"]);
			$result = array(
				"BLOCK" => $block,
				"DEPENDENCIES" => $this->dependencies,
				"META" => $this->meta,
				"VERSION" => self::CURRENT_VERSION
			);
		}
		return $result;
	}

	/**
	 * Сохраняет HL блок в файл
	 * @param string $blockName
	 * @return array
	 */
	public function backupBlock($blockName) {
		$dt = date("Y_m_d_His_");
		if ( !empty($block = $this->_getBlockInfoByName($blockName)) ){
			$md = $this->getMetaData($blockName);
		 	file_put_contents(
		 		$this->_getPath() . $dt . $blockName . ".json",
		 		json_encode($md, JSON_HEX_AMP)
 			);
		}
		return json_encode($md);
	}


	/**
	 * Восстанавливает HL блок из файла.
	 * @param type $filename
	 * @return integer id восстановленного блока
	 */
	public function restoreBlock($filename) {
		$meta = $this->getMetaDataFromFile($filename);
		// $meta = $this->_getBlockInfoByName($filename);
		// \Gb\Util::pr_var($meta, 'meta');
		// return;
		if ( empty($this->_getBlockInfoByName($meta["BLOCK"]["NAME"])) ) {
			$this->_restoreBlock( $meta );
		} else {
			throw new Exception("HL блок " . $meta["BLOCK"]["NAME"] . " существует", 403);
		}
	}


	/**
	 * Восстанавливает HL блоки из метаинформации
	 * @param array $meta
	 * @return array
	 */
	public function restore($meta) {
		$restored = false;
		if (is_array($meta)) {
			// Сначала восстанавливаем блоки, от которых зависят другие
			if (is_array($meta["DEPENDENCIES"])) {
				foreach ($meta["DEPENDENCIES"] as $dependency) {
					$blockId = $dependency["ID"];
					if ( !isset($restored[$blockId]) && (!$this->getBlockIdByName($name)) ) {
						$newId = $this->_restoreBlock($meta["META"][$blockId]);
						$restored[$blockId] = array(
							"OLD_ID" => $blockId,
							"NEW_ID" => $newId
						);
					}
				}
			}

			// Восстанавливаем оставшиеся блоки
			if (is_array($meta["META"])) {
				foreach ($meta["META"] as $meta1) {
					$blockId = $meta1["BLOCK"]["ID"];
					if ( !isset($restored[$blockId]) && (!$this->getBlockIdByName($name)) ) {
						$this->_restoreBlock($meta1);
						$restored[$blockId] = array(
							"OLD_ID" => $blockId,
							"NEW_ID" => $newId
						);
					}
				}
			}
		}
		return $restored;
	}

	/**
	 * Возвращает метаинформацию из файла
	 * @param type $filename
	 * @return array
	 */
    public function getMetaDataFromFile($filename) {
    	return json_decode( file_get_contents( $this->_getPath() . $filename . ".json"), true );
    }

	/**
	 * Возвращает из БД id блока по его имени
	 * @param string $blockName
	 * @return integer
	 */
	public function getBlockIdByName($blockName) {
		$result = $this->_getBlockInfoByName($blockName);
		return $result["ID"];
	}

	/**
	 * Имя блока по его id
	 * @param integer $blockId
	 * @return string
	 */
	public function getBlockName($blockId) {
		$result = $this->_getBlockInfoById($blockId);
		return $result["NAME"];
	}

	/**
	 * Возвращает массив файлов из папки, в которой хранится метаинформация HL блоков
	 * @return array
	 */
    public function getFiles() {
        return glob($this->_getPath() . "*.json");
    }

    /**
     * Возвращает информацию о блоках, которая хранится в файлах в папке с метаинформацией
     * @return array
     */
    public function getBlocksFromFiles() {
    	$blocksInFiles = $this->getFiles();
    	foreach ($blocksInFiles as $file) {
    		$block = json_decode( file_get_contents($file), true );
    		/* в зависимости от версии сохраненных данных обрабатывать нужно разные уровни вложенности */
    		if ( isset($block["META"]) ) {
				$curr = current($block["META"]);
    		} else {
				$curr = $block;
    		}
    		$curr["BLOCK"]["FILENAME"] = str_replace($this->_getPath(), "", $file);
    		$result[$curr["BLOCK"]["NAME"]] = $curr["BLOCK"];
    	}
    	return $result;
    }

    public function getMap($blockName)
    {
	   	$list = $this->getList();
    	foreach ($list as $value) {
    		$listBlocks[$value["ID"]] =$value["NAME"];
    	}

    	$blockId = array_search($blockName, $listBlocks);

		$obj = \CUserTypeEntity::GetList(
			array("ENTITY_ID" => "ASC"),
			array("ENTITY_ID" => "HLBLOCK_" . $blockId)
		);
		while($el = $obj->Fetch()) {
			$alias = \Gb\Util::camelize( substr($el["FIELD_NAME"], 3) );
			switch ($el["USER_TYPE_ID"]) {
				case "hlblock":
					// список возможных значений
					$result["FIELDS"][$alias . "Name"] = array(
						"name" => "UF_NAME",
			            "data_type" => "\\" . $listBlocks[$el["SETTINGS"]["HLBLOCK_ID"]],
			            "reference" => array(
			                "=this." . $el["FIELD_NAME"] => "ref.ID"
			            ),
					);
					$alias .= "Id";
					$field = $el["FIELD_NAME"];
					break;
				default:
					// список возможных значений
					$field = $el["FIELD_NAME"];
					break;
			}
			$result["FIELDS"][$alias] = $field;
		}

    	return $result;
    }

    /**
     * Возвращает поля HL блока
     * @param  array  $params
     * @return array
     */
    public function getFields(array $params = array())
    {
    	$result = false;
    	$uf = new \Gb\Element\UserField;
    	if ( isset($params["filter"]["blockName"]) ) {
    		$list = $uf->getList(array(
		   		"filter" => array(
					"blockName" => $params["filter"]["blockName"]
				)
	   		));
	   		unset($params["filter"]["blockName"]);
	   		$cur = current($list);
	   		$params["filter"]["ENTITY_ID"] = "HLBLOCK_" . $cur["ID"];
    	}

		$obj = \CUserTypeEntity::GetList(
			$params["order"],
			$params["filter"]
		);
		while($el = $obj->Fetch()) {
			$result[$el["ID"]] = $el;
		}
		return $result;
	}

	/**
	 * Записывает текст класса по имени HL блока
	 * @param  string $blockName имя HL блока
	 * @return void
	 */
    public function saveClassText($blockName)
    {
    	$filename = $_SERVER["DOCUMENT_ROOT"] . self::PATH_TO_CLASSES . $this->getClassName($blockName) . ".php";
    	file_put_contents($filename, $this->getClassText($blockName));

    }

	/**
	 * Генерирует текст класса по имени HL блока
	 * @param  string $blockName имя HL блока
	 * @return void
	 */
    public function getClassText($blockName)
    {
    	$className = $this->getClassName($blockName);
    	$uf = new \Gb\Element\UserField;
		$fields = $uf->getList(array(
			"filter" => array(
				"blockName" => $blockName,
				"FIELD_NAME" => "UF_DELETED",
			)
		));

    	$result = "<?
namespace " . self::NAMESPACE_FOR_SAVE. ";
class $className extends \Akop\Element\HlElement
{
	protected \$entityName = '$className';";
		if (!empty($fields)) {
			$result .= PHP_EOL . "    protected \$softDelete = true;";
		}
		$result .= PHP_EOL . "}";
    	return $result;
    }

    public function getClassName($blockName)
    {
    	return substr($blockName, strlen(self::TABLE_PREFIX));
    }

	private function _getMetaData($blockName) {
		$this->meta[$blockName] = $this->_getMetaDataWODependencies($blockName);

		foreach ($this->allDependencies as $dependency) {
			// если еще не получена информация по данному блоку, то получим ее
			if ( !isset($this->meta[$dependency]) ) {
				$this->_getMetaData($dependency);
			}
		}
		return $this->meta;
	}


	private function _getMetaDataWODependencies($blockName) {
		if ( $result["BLOCK"] = $this->_getBlockInfoByName($blockName) ) {
			/* Получаем список полей блока */
			$obj = \CUserTypeEntity::GetList(
				array("ENTITY_ID" => "ASC"),
				array("ENTITY_ID" => "HLBLOCK_" . $result["BLOCK"]["ID"])
			);
			while($el = $obj->Fetch()) {
				/* получаем данные о поле по его ID */
				$field = \CUserTypeEntity::GetByID($el["ID"]);
				/* для перечислимого поля получаем список возможных значений */
				switch ($field["USER_TYPE_ID"]) {
					case "enumeration":
						// список возможных значений
						$enumValues = $this->_getEnumValues($el["ID"]);
						$field["EXTRA_DATA"] = $enumValues;
						break;
					case "hlblock":
						// список возможных значений
						$enumValues = $this->_getEnumValues($el["ID"]);
						$extraField = \CUserTypeEntity::GetByID($field["SETTINGS"]["HLFIELD_ID"]);

						$field["EXTRA_DATA"] = array(
							"FIELD_NAME" => $extraField["FIELD_NAME"],
							"HLBLOCK_NAME" => $result["BLOCK"]["NAME"],
							"XF" => $extraField
						);

						$this->addDependency(
							$result["BLOCK"]["NAME"],
							$this->getBlockName($field["SETTINGS"]["HLBLOCK_ID"])
						);
						break;
				}
				$result["FIELDS"][$el["FIELD_NAME"]] = $field;
			}
		}
	  	return $result;
	}


    private function addDependency($nodeName, $dependOn)
    {
    	if ( !in_array($dependOn, $this->dependencies[$nodeName]) ) {
			$this->dependencies[$nodeName][] = $dependOn;
    	}

    	if ( !in_array($dependOn, $this->allDependencies) ) {
			$this->allDependencies[] = $dependOn;
    	}
	}

    /**
     * Восстанавливает HL блок из переданного массива.
     * @param array $meta метаинформация для восстановления болка
     * @return integer ID восстановленного блока
     * @todo сделать восстановление блоков, от которых зависит этот блок
     */
    private function _restoreBlock($meta)
    {
    	$blockId = $this->_createBlock($meta["BLOCK"]);

    	// Создаем поля HL блока
    	$objUF = new \CUserTypeEntity;
    	foreach ($meta["META"][$meta["BLOCK"]["NAME"]]["FIELDS"] as $fields) {
    		unset($fields["ID"]);
    		$fields["ENTITY_ID"] = "HLBLOCK_" . $blockId;
    		// Создаем поле
    		$id = $objUF->Add($fields);
    		// Добавление списка возможных значений для перечислимых полей
    		if ($id) {
    			switch ($fields["USER_TYPE_ID"]) {
    				case "enumeration":
    					$this->_setEnumValues($id, $fields["EXTRA_DATA"]);
    					break;
    				case "hlblock":
    					$this->_setEnumValues($id, $fields["EXTRA_DATA"]);
    					break;
    				default:
    					break;
    			}
    		}
    	}
    	return $blockId;

    }

    /**
     * Создает HL блок
     * @param  array $meta [Название блока, Название таблицы в БД]
     * @return int id блока
     */
    private function _createBlock($meta)
    {
    	unset($meta["ID"]);
    	// Создаем HLBlock
    	$result = HLB\HighloadBlockTable::add($meta);
    	$blockId = $result->getId();
    	/* обновим данные о HLBlock-ах */
    	$hldata = HLB\HighloadBlockTable::getById($blockId)->fetch();
    	$hlentity = HLB\HighloadBlockTable::compileEntity($hldata);
    	return $blockId;
    }

    /**
     * Сохраняет метаданные в файл
     * @param  [type] $blockId [description]
     * @return [type]          [description]
     */
    private function _saveMetaData($blockId) {
    	$block = $this->_getMetaDataWODependencies($blockId);
    	return file_put_contents($this->_getPath() . $block["BLOCK"]["NAME"] . ".json", json_encode($block, JSON_HEX_AMP));
    }

    private function _getPath() {
    	return $_SERVER["DOCUMENT_ROOT"] . self::PATH_TO_META;
    }

	private function _getBlockInfoById($blockId) {
		return HLB\HighloadBlockTable::getRow(array(
			'filter'=>array('ID' => $blockId)
		));
	}

	private function _getBlockInfoByName($blockName) {
		return HLB\HighloadBlockTable::getRow(array(
			'filter'=>array('NAME' => $blockName)
		));
	}

	/**
	 * Получаем возможные значения перечислимого типа
	 * @param  [type] $fieldId [description]
	 * @return [type]          [description]
	 */
	private function _getEnumValues($fieldId) {
		$obj = new \CUserFieldEnum();
		$list = $obj->GetList(
			array(),
			array("USER_FIELD_ID" => $fieldId)
		);
		while ($el = $list->Fetch()) {
			$result[$el["ID"]] = $el;
		}
		return $result;
	}


	/**
	 * Устанавливаем возможные значения перечислимого типа
	 * @param [type] $fieldId [description]
	 * @param [type] $values  [description]
	 */
	private function _setEnumValues($fieldId, $values) {
		$result = false;
		if (isset($fieldId) && is_array($values) && count($values)) {
			/* Проверяем наличие значений у перечислимого поля */
			$obj = new \CUserFieldEnum();
			$list = $obj->GetList(
				array(),
				array("USER_FIELD_ID" => $fieldId)
			);
			while ($el = $list->Fetch()) {
				$valuesExist[$el["XML_ID"]] = $el;
			}

			foreach ($values as $key => $value) {
				if (!isset($valuesExist[$value["XML_ID"]])) {
					$newValues[$value["XML_ID"]] = array(
						"VALUE" => $value["XML_ID"],
						"DEF" => $value["DEF"],
						"SORT" => $value["SORT"],
						"XML_ID" => $value["XML_ID"],
					);
				}
				$xmlIds[] = $value["XML_ID"];
			}

			if (is_array($newValues) && count($newValues)) {
				$result = $obj->setEnumValues($fieldId, $newValues);
			}
		}

		return $result;
	}

}
?>