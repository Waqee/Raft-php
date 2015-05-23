<?php
include 'NodeProperties.php';

class NodeList
{
	public $Nodes;

	public function __construct()
	{
		$this->Nodes = array();
	}

	public function AddNode($Node)
	{
		$this->Nodes[] = $Node;
	}
}
