<?php 

class Log
{
	public $command;

	public $term;

	public function __construct($term, $command)
	{
		$this->command = $command;
		$this->term = $term;
	}	
}