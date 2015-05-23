<?php
include 'RequestVote.php';
include 'ResponseVote.php';
include 'AppendEntries.php';
include 'EntryResult.php';

class Message
{
	public $Type;

	public $RequestVote;

	public $ResponseVote;

	public $AppendEntries;

	public $EntryResult;

	public $Command;

	public $Reply;

	public function __construct($Type, $Message)
	{
		$this->Type = $Type;
		if($this->Type == "RequestVote")
			$this->RequestVote = $Message;
		else if($this->Type == "ResponseVote")
			$this->ResponseVote = $Message;
		else if($this->Type == "AppendEntries")
			$this->AppendEntries = $Message;
		else if($this->Type == "EntryResult")
			$this->EntryResult = $Message;
		else if($this->Type == "Command")
			$this->Command = $Message;
		else if($this->Type == "Reply")
			$this->Command = $Message;
	}
}
