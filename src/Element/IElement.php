<?php

namespace Akop\Element;

/**
 * Класс описывает интерфейс, который должны имплементировать классы, работающие с элементами
 * @author: Андрей Копылов
 * @mail: aakopylov@mail.ru,
 * @skype: andrew.kopylov.74
 */
interface IElement
{
	public function getList(array $params = array());
	public function getRow(array $params = array());
}
?>