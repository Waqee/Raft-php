<?php

class ResponseVote
{
	public $Term;

	public $VoteGranted;

	public function __construct($Term, $VoteGranted)
	{
		$this->Term = $Term;
		$this->VoteGranted = $VoteGranted;
	}
}
