<?php

class AppendEntries
{
	public $Term;

	public $LeaderId;

	public $PrevLogIndex;

	public $PrevLogTerm;

	public $Entries;

	public $LeaderCommit;

	public function __construct($Term, $LeaderId, $PrevLogIndex, $PrevLogTerm, $Entries, $LeaderCommit)
	{
		$this->Term = $Term;
		$this->LeaderId = $LeaderId;
		$this->PrevLogIndex = $PrevLogIndex;
		$this->PrevLogTerm = $PrevLogTerm;
		$this->Entries = $Entries;
		$this->LeaderCommit = $LeaderCommit;
	}
}
