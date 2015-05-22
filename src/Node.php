<?php
include 'NodeList.php';
include 'States/State.php';

class Node
{
	public $MyProperties;

	public $NodeList;

	public $Sender;

	public $Reciever;

	public $state;

	public $currentTerm;

	public $votedFor;

	public $votes;
	
	public $log;

	public $LeaderId;

	public $commitIndex;

	public $lastApplied;

	public $lastLogIndex;

	public $nextIndex;

	public $matchIndex;

	public $timeoutinterval;

	public $heartbeatinterval;

	public $timeout;

	public $heartbeat;

	public $socket;

	public $clientSocket;

	public $leader;

	public $close;

	public $exiting;

	public $exit_time;

	public $log_writer;


    public function __construct($MyProperties, $NodeList)
    {
		$this->MyProperties = $MyProperties;
        $this->NodeList = $NodeList;
		$this->Sender = new Sender($this->MyProperties);
		$this->Reciever = new Reciever($this->NodeList);
		$this->state = "Initialized";
		$this->currentTerm = 0;
		$this->votedFor = null;
		$this->votes = 0;
		$this->log = array();
		$this->commitIndex = 0;
		$this->lastApplied = 0;
		$this->lastLogIndex = 0;
		$this->nextIndex = array();
		$this->matchIndex = array();
		$this->timeoutinterval = 0.15;
		$this->heartbeatinterval = 0.075;
		$this->LeaderId = null;
		$this->close = false;
		set_time_limit(0);
		$this->socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
		socket_bind($this->socket, $MyProperties->ServerAddr, $MyProperties->PortNo+5);
		socket_listen($this->socket);
		$this->clientSocket = false;
		socket_set_nonblock($this->socket);
		$this->exiting = false;
		$this->exitcount = 0;
    }
	
	public function NodeStart()
	{
		$this->state = "Follower";
		$x = 0;
		$y = 0; 
		while(count($this->NodeList->Nodes) != $x || count($this->NodeList->Nodes) != $y)
		{
			if(count($this->NodeList->Nodes) != $x)
				$x = $this->Sender->AcceptConnection($this->MyProperties);

			if(count($this->NodeList->Nodes) != $y)
				$y = $this->Reciever->TryConnections($this->MyProperties, $this->NodeList);
			#echo $this->MyProperties->Id." ".$x." ".$y."\n";
		}
		echo "A";
		$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
		$this->NodeRunning();
		$this->NodeClose();
	}

	public function NodeRunning()
	{
		while(true)
		{			
			// echo "Log : ";

			// foreach ($this->log as $key => $value) {
			// 	echo " ".$value->command;
			// }

			// echo "\n";

			if($this->clientSocket==false)
				$this->clientSocket = socket_accept($this->socket);
			
			if($this->clientSocket!=false)
			{
				socket_set_nonblock($this->clientSocket);

				$command = socket_read($this->clientSocket, 100);


				if(strlen($command)!=0)
				{
					echo "$this->state Command $command";
					if($this->state == "Leader")
					{
						if($command == "Sleep")
							sleep(5);
						if($command == "quit")
						{
							echo "Quited";
							return;
						}
						else
						{
							$this->lastLogIndex += 1;
							$this->log[$this->lastLogIndex] = new Log($this->currentTerm, $command);
							$this->WriteLog();
						}
					}
					else
					{
						socket_write($this->clientSocket, $this->LeaderId, 20);
						socket_close($this->clientSocket);
						$this->clientSocket = false;
					}
				}
			}

			if($this->state == "Leader" && microtime(true) >= $this->leader+2)
			{
				$this->leader = 10000000;
				// sleep(3);
			}


			$Messages = $this->Reciever->TryRecieve();

			//if(count($Messages)!=0)
			//	echo "Messages : ".count($Messages)."\n";

			if($this->exiting == true && time()> $this->exit_time + 5)
			{
					return;
			}

			foreach ($Messages as $key => $value) {
				//$this->WriteLog();
				$drop = rand(0,30);

				if($drop==0)
					continue;
				if($value->Type == "RequestVote")
				{
					//echo "ID ".$this->MyProperties->Id." $this->state RequestVote \n";
					if($value->RequestVote->Term<=$this->currentTerm)
					{
						$Message = new Message("ResponseVote",new ResponseVote($this->currentTerm,"False"));
						$this->Sender->SendMessage($value->RequestVote->CandidateId, $Message);
					}
					else
					{
						$this->state = "Follower";
						$this->currentTerm = $value->RequestVote->Term;
						$this->votedFor = $value->RequestVote->CandidateId;
						$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
						$Message = new Message("ResponseVote",new ResponseVote($this->currentTerm,"True"));
						$this->Sender->SendMessage($value->RequestVote->CandidateId, $Message);
					}
				}
				else if($value->Type == "ResponseVote" && $this->state == "Candidate")
				{
					//echo "ID ".$this->MyProperties->Id." $this->state Response \n";
					if($value->ResponseVote->VoteGranted=="True")
						$this->votes+=1;
					else if($value->ResponseVote->Term>$this->currentTerm)
					{
						$this->state = "Follower";
						$this->currentTerm = $value->ResponseVote->Term;
						$this->votedFor = $value->RequestVote->CandidateId;
						$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
					}
				}
				else if ($value->Type == "AppendEntries")
				{
					//echo "ID ".$this->MyProperties->Id." $this->state AppendEntries \n";
					if($value->AppendEntries->Term<$this->currentTerm)
					{
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"False", $this->MyProperties->Id));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);
					}
					else if($value->AppendEntries->PrevLogIndex!=0 && !array_key_exists($value->AppendEntries->PrevLogIndex,$this->log))
					{
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"False", $this->MyProperties->Id));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);
						$this->LeaderId = $value->AppendEntries->LeaderId;
					}
					else if($value->AppendEntries->PrevLogIndex!=0 && array_key_exists($value->AppendEntries->PrevLogIndex,$this->log) && $this->log[$value->AppendEntries->PrevLogIndex]->term != $value->AppendEntries->PrevLogTerm)
					{
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"False", $this->MyProperties->Id));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);
						$this->LeaderId = $value->AppendEntries->LeaderId;
					}
					else
					{
						$this->LeaderId = $value->AppendEntries->LeaderId;
						if($value->AppendEntries->Term > $this->currentTerm)
						{
							$this->state = "Follower";
							$this->currentTerm = $value->AppendEntries->Term;
							$this->votedFor = $value->AppendEntries->LeaderId;
						}
						$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
						$contains = false;

						
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"True", $this->MyProperties->Id));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);

						if($this->state != "Follower")
							echo "WTF";

						$delentries = false;

						for ($i=$value->AppendEntries->PrevLogIndex+1, $j=0;;$i+=1,$j+=1)
						{
							if($delentries)
							{
								if(isset($this->log[$i]))
									unset($this->log[$i]);
								else
									break;
							}
							else if(!isset($value->AppendEntries->Entries[$j]))
							{
								$delentries = true;
								$this->lastLogIndex = $i-1;
								if(isset($this->log[$i]))
									unset($this->log[$i]);
							}
							else
								$this->log[$i] = $value->AppendEntries->Entries[$j];
						}

						$this->WriteLog();

						if($value->AppendEntries->LeaderCommit > $this->commitIndex)
						{
							if($this->lastLogIndex < $value->AppendEntries->LeaderCommit)
								$this->commitIndex = $this->lastLogIndex;
							else
								$this->commitIndex = $value->AppendEntries->LeaderCommit;
						}
					}
				}
				else if ($value->Type == "EntryResult")
				{
					//echo "ID ".$this->MyProperties->Id." $this->state EntryResult \n";
					if($value->EntryResult->Term>$this->currentTerm)
					{
						$this->state = "Follower";
						$this->currentTerm = $value->EntryResult->Term;
						$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
					}
					else if($value->EntryResult->Success == "False")
					{
						if($this->nextIndex[$value->EntryResult->id]>0)
						$this->nextIndex[$value->EntryResult->id]-=1;
					}
					else if($value->EntryResult->Success == "True")
					{
						$this->nextIndex[$value->EntryResult->id] = $this->lastLogIndex;
						$this->matchIndex[$value->EntryResult->id] = $this->lastLogIndex;
					}
				}

				if($this->commitIndex > $this->lastApplied)
			{
				$this->lastApplied+=1;
				if($this->state == "Leader")
					socket_write($this->clientSocket, $this->log[$this->lastApplied]->command , 20);

				if($this->log[$this->lastApplied]->command == "Exit")
				{
					if($this->state == "Leader")
						return;
						
				}
				else if($this->log[$this->lastApplied]->command == "Quit")
				{
					socket_close($this->clientSocket);
					$this->clientSocket = false;
				}
				$this->WriteLog();

				//echo "ID ".$this->MyProperties->Id." $this->state Applied ".$this->lastApplied." Term : ".$this->log[$this->lastApplied]->term." Command ".$this->log[$this->lastApplied]->command."\n";
			}

			if($this->state == "Candidate" && $this->votes > (count($this->NodeList)+1)/2)
			{
				echo "ID ".$this->MyProperties->Id." $this->state Won Election \n";
				$this->state = "Leader";
				$Message = new Message("AppendEntries", new AppendEntries($this->currentTerm,$this->MyProperties->Id, $this->lastLogIndex, $this->lastLogIndex>0?$this->log[$this->lastLogIndex]->term:null, null, $this->commitIndex));
				$this->Sender->BroadCastMessage($Message);
				$this->heartbeat = array(); 
				foreach ($this->NodeList->Nodes as $index => $iNode)
					$this->heartbeat[$iNode->Id] = microtime(true) + $this->heartbeatinterval;

				$this->leader = microtime(true);
			}
			else if($this->state!="Leader" && microtime(true)>=$this->timeout)
			{
				echo "ID ".$this->MyProperties->Id." $this->state Started Election \n";
				$this->state = "Candidate";
				$this->currentTerm+=1;
				$this->votes = 1;
				$this->votedFor = $this->MyProperties->Id;
				$Message = new Message("RequestVote", new RequestVote($this->currentTerm,$this->MyProperties->Id, $this->lastLogIndex, $this->lastLogIndex>0?$this->log[$this->lastLogIndex]->term:null));
				$this->Sender->BroadCastMessage($Message);
				$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000);
				$this->nextIndex = array(); 
				$this->matchIndex = array();
				foreach ($this->NodeList->Nodes as $index => $iNode)
				{
					$this->nextIndex[$iNode->Id] = $this->lastLogIndex;
					$this->matchIndex[$iNode->Id] = 0;
				}
			}
			else if($this->state == "Leader")
			{			
				for($i = max($this->matchIndex); $i>$this->commitIndex; $i-=1)
				{
					if( $this->log[$i]->term != $this->currentTerm)
						continue;
					$matchcount = 0;
					foreach ($this->matchIndex as $key => $value) {
						if( $value == $i)
							$matchcount+=1;
					}
					if($matchcount >= (int)((count($this->NodeList->Nodes)+1)/2))
					{
						$this->commitIndex = $i;
						break;
					}
				}
				foreach ($this->nextIndex as $id => $index) {
					
					if(microtime(true)>=$this->heartbeat[$id])
					{
						//echo "ID ".$this->MyProperties->Id." $this->state HeartBeat to $id \n";
						$entries = array();
						for($i = $index+1; $i<=$this->lastLogIndex; $i+=1)
							$entries[]=$this->log[$i];
						if(count($entries)!=0)
							echo "Try\n";
						$Message = new Message("AppendEntries", new AppendEntries($this->currentTerm,$this->MyProperties->Id, $index, $index>0?$this->log[$index]->term:null, $entries, $this->commitIndex));
						$this->Sender->SendMessage($id, $Message);
						$this->heartbeat[$id] = microtime(true) + $this->heartbeatinterval;
					}
						
				}
			}
				
			}

			if(count($Messages)==0)
			{
				if($this->commitIndex > $this->lastApplied)
			{
				$this->lastApplied+=1;
				if($this->state == "Leader")
					socket_write($this->clientSocket, $this->log[$this->lastApplied]->command , 20);

				if($this->log[$this->lastApplied]->command == "Exit")
				{
					if($this->state == "Leader")
						return;
				}
				else if($this->log[$this->lastApplied]->command == "Quit")
				{
					socket_close($this->clientSocket);
					$this->clientSocket = false;
				}
				$this->WriteLog();

				//echo "ID ".$this->MyProperties->Id." $this->state Applied ".$this->lastApplied." Term : ".$this->log[$this->lastApplied]->term." Command ".$this->log[$this->lastApplied]->command."\n";
			}

			if($this->state == "Candidate" && $this->votes > (count($this->NodeList)+1)/2)
			{
				//echo "ID ".$this->MyProperties->Id." $this->state Won Election \n";
				$this->state = "Leader";
				$Message = new Message("AppendEntries", new AppendEntries($this->currentTerm,$this->MyProperties->Id, $this->lastLogIndex, $this->lastLogIndex>0?$this->log[$this->lastLogIndex]->term:null, null, $this->commitIndex));
				$this->Sender->BroadCastMessage($Message);
				$this->heartbeat = array(); 
				foreach ($this->NodeList->Nodes as $index => $iNode)
					$this->heartbeat[$iNode->Id] = microtime(true) + $this->heartbeatinterval;

				$this->leader = microtime(true);
			}
			else if($this->state!="Leader" && microtime(true)>=$this->timeout)
			{
				//echo "ID ".$this->MyProperties->Id." $this->state Started Election \n";
				$this->state = "Candidate";
				$this->currentTerm+=1;
				$this->votes = 1;
				$this->votedFor = $this->MyProperties->Id;
				$Message = new Message("RequestVote", new RequestVote($this->currentTerm,$this->MyProperties->Id, $this->lastLogIndex, $this->lastLogIndex>0?$this->log[$this->lastLogIndex]->term:null));
				$this->Sender->BroadCastMessage($Message);
				$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000);
				$this->nextIndex = array(); 
				$this->matchIndex = array();
				foreach ($this->NodeList->Nodes as $index => $iNode)
				{
					$this->nextIndex[$iNode->Id] = $this->lastLogIndex;
					$this->matchIndex[$iNode->Id] = 0;
				}
			}
			else if($this->state == "Leader")
			{
				for($i = max($this->matchIndex); $i>$this->commitIndex; $i-=1)
				{
					if( $this->log[$i]->term != $this->currentTerm)
						continue;
					$matchcount = 0;
					foreach ($this->matchIndex as $key => $value) {
						if( $value == $i)
							$matchcount+=1;
					}
					if($matchcount >= (int)((count($this->NodeList->Nodes)+1)/2))
					{
						$this->commitIndex = $i;
						break;
					}
				}

				
				foreach ($this->nextIndex as $id => $index) {
					
					if(microtime(true)>=$this->heartbeat[$id])
					{
						//echo "ID ".$this->MyProperties->Id." $this->state HeartBeat to $id \n";
						$entries = array();
						for($i = $index+1; $i<=$this->lastLogIndex; $i+=1)
							$entries[]=$this->log[$i];
						if(count($entries)!=0)
							echo "Try\n";
						$Message = new Message("AppendEntries", new AppendEntries($this->currentTerm,$this->MyProperties->Id, $index, $index>0?$this->log[$index]->term:null, $entries, $this->commitIndex));
						$this->Sender->SendMessage($id, $Message);
						$this->heartbeat[$id] = microtime(true) + $this->heartbeatinterval;
					}
						
				}
			}
			}

			


		}
	}

	public function WriteLog()
	{
		$file = "log".$this->MyProperties->Id;
		$content = "\n".$this->commitIndex." ".$this->lastApplied." ".$this->lastLogIndex." ".$this->MyProperties->Id."     ";
		foreach ($this->log as $id => $index)
			$content = $content.$index->command." ";
		$content = $content."\n";
		echo($content);
	}

	public function NodeClose()
	{
		socket_close($this->clientSocket);
		socket_close($this->socket);
		$this->Reciever->CloseConnections();
		$this->Sender->CloseConnections();
		
		//echo "AS".$this->MyProperties->Id;
	}
}
