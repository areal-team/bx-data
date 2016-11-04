<?
namespace Gb\Meta;

/**
 * Разрешение зависимостей
 */
class DependencyResolver
{
	private
		$resolved = array(),
		$edges = array(),
		$unresolved = array(),
		$nodes = array();

	/**
	 * Разрешает зависимости
	 * @param  string $nodeName Имя с которого начинаем разрешение зависимостей, по умолчанию это первый элемент
	 * @return [type]            [description]
	 */
	public function resolve($nodeName = "")
	{
		$edges = ( empty($nodeName) )
			? current($this->edges)
			: isset($this->edges[$nodeName])
				? $this->edges[$nodeName]
				: false;

		$this->unresolved[] = $nodeName;
		if ( !empty($edges) ) {
		    foreach ($edges as $dependent => $dependOn) {
		   		if ( !in_array($dependOn, $this->resolved) ) {
		   			if ( in_array($dependOn, $this->unresolved) ) {
		   				throw new \Exception(
		   					sprintf("Circular reference detected: %s -> %s", $nodeName, $dependOn),
		   					424
	   					);
		   			}
			        $this->resolve($dependOn);
		   		}
		    }
		}

	    $this->resolved[] = $nodeName;
		array_splice($this->unresolved, array_search($nodeName, $this->unresolved), 1);
	    return $this->resolved;
	}

	/**
	 * Добавляет 1 узел в граф
	 * @param string $nodeName [description]
	 */
	public function addNode($nodeName)
	{
		$this->nodes[] = $nodeName;
	}

	/**
	 * Добавляет несколько узлов в граф
	 * @param array $nodes
	 */
	public function addNodes(array $nodes)
	{
		foreach ($nodes as $node) {
			$this->addNode($node);
		}
	}

	/**
	 * Добавляет ребро в граф (зависимость перввого элемента от второго)
	 * @param string $nodeName зависимый элемент
	 * @param string $dependOn от чего зависит элемент
	 */
	public function addEdge($nodeName, $dependOn)
	{
		$this->edges[$nodeName][] = $dependOn;
	}

	public function getNodes()
	{
		return $this->nodes;
	}

	public function getEdges()
	{
		return $this->edges;
	}
}
