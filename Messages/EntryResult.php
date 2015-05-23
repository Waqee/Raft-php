<?php

class EntryResult
{
	public $Term;

	public $Success;

	public $id;

	public $index;

	public function __construct($Term, $Success, $id, $index)
	{
		$this->Term = $Term;
		$this->Success = $Success;
		$this->id = $id;
		$this->index = $index;
	}
}
