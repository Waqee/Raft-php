<?php

class RequestVote
{
	public $Term;

	public $CandidateId;

	public $LastLogIndex;

	public $LastLogTerm;

	public function __construct($Term, $CandidateId, $LastLogIndex, $LastLogTerm)
	{
		$this->Term = $Term;
		$this->CandidateId = $CandidateId;
		$this->LastLogIndex = $LastLogIndex;
		$this->LastLogTerm = $LastLogTerm;
	}
}
