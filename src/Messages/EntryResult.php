<?php

class EntryResult
{
	public $Term;

	public $Success;

	public $id;

	public function __construct($Term, $Success, $id)
	{
		$this->Term = $Term;
		$this->Success = $Success;
		$this->id = $id;
	}
}
