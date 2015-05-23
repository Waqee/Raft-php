<?php

class NodeProperties
{
	public $Id;

	public $ServerAddr;

	public $PortNo;

	public function __construct($Id, $ServerAddr, $PortNo)
	{
		$this->Id = $Id;
		$this->ServerAddr = $ServerAddr;
		$this->PortNo = $PortNo;
	}
}
