<?php
include 'Log.php';
include 'Connections/Sender.php';
include 'Connections/Reciever.php';

class State
{
	

	public $Sender;

	public $Reciever;
    
	public function __construct($Sender, $Reciever)
	{
		
		$this->Sender = $Sender;
		$this->Reciever = $Reciever;
	}

	public function MainLoop()
	{

	}

	public function LeaderElectionPhase($Messages)
	{
		if($this->state == "Follower")
		{

		}
		else if($this->state == "Candidate")
		{

		}
		else if($this->state == "Leader")
		{

		}
	}

	public function LogReplicationPhase($Messages)
	{
		if($this->state == "Follower")
		{

		}
		else if($this->state == "Candidate")
		{

		}
		else if($this->state == "Leader")
		{

		}
	}
}

