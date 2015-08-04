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

	public $connectIndex;


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
		$this->connectIndex = 0;
		set_time_limit(0);
		$this->socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
		socket_bind($this->socket, $MyProperties->ServerAddr, $MyProperties->PortNo+count($NodeList->Nodes)+1);
		socket_listen($this->socket);
		$this->clientSocket = false;
		socket_set_nonblock($this->socket);
		$this->exiting = false;
		$this->exitcount = 0;
    }
	
	public function NodeStart()
	{
		mkdir("./Node ".$this->MyProperties->Id);
		$myFile = "Node ".$this->MyProperties->Id."/Debug.txt";
		$fh = fopen($myFile, 'w');
		$content = "Time\tCommitID\tLogID\tNodeID\tLogs";
		fwrite($fh, $content);
		fclose($fh);
		$this->state = "Follower";
		$x = 0;
		$y = 0; 
		while($this->connectIndex<=count($this->NodeList->Nodes))
		{
			if($this->MyProperties->Id == $this->connectIndex)
				$this->Sender->AcceptConnection($this->MyProperties, count($this->NodeList->Nodes));
			else
			{
				sleep(1);
				$this->Reciever->TryConnections($this->MyProperties, $this->NodeList, $this->connectIndex);
			}
			#echo $this->MyProperties->Id." ".$x." ".$y."\n";
			$this->connectIndex += 1;
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
			{
				$read = array($this->socket);
				if(socket_select($read,$write = NULL, $except = NULL, 0)>0)
					$this->clientSocket = socket_accept($this->socket);
			}
			
			if($this->clientSocket!=false)
			{
				socket_set_nonblock($this->clientSocket);

				$command = socket_read($this->clientSocket, 100);


				if(strlen($command)!=0)
				{
					echo "$this->state Command $command";
					if($command == "Sleep")
							sleep(5);
					else if($command == "quit")
					{
						echo "Quited";
						return;
					}
					else if($command == "closesock")
					{
						$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
					    socket_set_block($this->clientSocket);
					    socket_set_option($this->clientSocket, SOL_SOCKET, SO_LINGER, $arrOpt);
						socket_close($this->clientSocket);
						$this->clientSocket = false;
					}
					else if($this->state == "Leader")
					{
						
							$this->lastLogIndex += 1;
							$this->log[$this->lastLogIndex] = new Log($this->currentTerm, $command);
							$this->WriteLog();
						
					}
					else
					{
						echo "Leaader ".$this->MyProperties->Id."\n";
						socket_write($this->clientSocket, $this->LeaderId, 20);
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

			

			foreach ($Messages as $key => $value) {
				//$this->WriteLog();
				
				$drop = rand(0,30);
				if($drop==0)
					continue;

				if($value->Type == "RequestVote")
				{
					echo "ID ".$this->lastLogIndex." ".$value->RequestVote->LastLogIndex." ".$this->MyProperties->Id." $this->state RequestVote \n";
					if($value->RequestVote->Term<=$this->currentTerm)
					{
						echo "false";
						$Message = new Message("ResponseVote",new ResponseVote($this->currentTerm,"False"));
						$this->Sender->SendMessage($value->RequestVote->CandidateId, $Message);
						
					}
					else if( $this->lastLogIndex > $value->RequestVote->LastLogIndex || ($this->lastLogIndex>0 &&$this->log[$this->lastLogIndex]->term != $value->RequestVote->LastLogTerm))
					{
						echo "changed to follower ".$value->RequestVote->CandidateId." false";
						$Message = new Message("ResponseVote",new ResponseVote($this->currentTerm,"False"));
						$this->Sender->SendMessage($value->RequestVote->CandidateId, $Message);
						$this->State = "Follower";
						$this->currentTerm = $value->RequestVote->Term;
						$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
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
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"False", $this->MyProperties->Id,$this->lastLogIndex));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);
					}
					else if($value->AppendEntries->PrevLogIndex!=0 && !array_key_exists($value->AppendEntries->PrevLogIndex,$this->log))
					{
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"False", $this->MyProperties->Id,$this->lastLogIndex));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);
						$this->LeaderId = $value->AppendEntries->LeaderId;
					}
					else if($value->AppendEntries->PrevLogIndex!=0 && array_key_exists($value->AppendEntries->PrevLogIndex,$this->log) && $this->log[$value->AppendEntries->PrevLogIndex]->term != $value->AppendEntries->PrevLogTerm)
					{
						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"False", $this->MyProperties->Id,$this->lastLogIndex));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);
						$this->LeaderId = $value->AppendEntries->LeaderId;
					}
					else
					{
						$this->LeaderId = $value->AppendEntries->LeaderId;
						if($value->AppendEntries->Term >= $this->currentTerm)
						{
							$this->state = "Follower";
							$this->currentTerm = $value->AppendEntries->Term;
							$this->votedFor = $value->AppendEntries->LeaderId;
						}
						$this->timeout = microtime(true) + $this->timeoutinterval + (mt_rand(0,150)/1000); 
						$contains = false;


						if($this->state != "Follower")
							echo "WTF";

						$delentries = false;

						$j=0;

						for ($i=$value->AppendEntries->PrevLogIndex+1;;$i+=1)
						{
							if($delentries)
							{
								if(isset($this->log[$i]))
								{
									unset($this->log[$i]);
								}
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
							{
								$this->log[$i] = $value->AppendEntries->Entries[$j];
								$j+=1;
							}
						}

						$Message = new Message("EntryResult",new EntryResult($this->currentTerm,"True", $this->MyProperties->Id,$this->lastLogIndex));
						$this->Sender->SendMessage($value->AppendEntries->LeaderId, $Message);

						if($j==0)
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
						$this->nextIndex[$value->EntryResult->id] = $value->EntryResult->index;
						$this->matchIndex[$value->EntryResult->id] = $value->EntryResult->index;
					}
				}

				if($this->commitIndex > $this->lastApplied)
			{
				$this->lastApplied+=1;
				if($this->state == "Leader")
					socket_write($this->clientSocket, $this->log[$this->lastApplied]->command , 20);

				$this->WriteLog();

				//echo "ID ".$this->MyProperties->Id." $this->state Applied ".$this->lastApplied." Term : ".$this->log[$this->lastApplied]->term." Command ".$this->log[$this->lastApplied]->command."\n";
			}

			if($this->state == "Candidate" && $this->votes >= (int)(count($this->NodeList)+1)/2)
			{
				echo "ID ".$this->MyProperties->Id." $this->state Won Election \n";
				$this->state = "Leader";
				$Message = new Message("AppendEntries", new AppendEntries($this->currentTerm,$this->MyProperties->Id, $this->lastLogIndex, $this->lastLogIndex>0?$this->log[$this->lastLogIndex]->term:null, null, $this->commitIndex));
				$this->Sender->BroadCastMessage($Message);
				$this->heartbeat = array(); 
				foreach ($this->NodeList->Nodes as $index => $iNode)
					$this->heartbeat[$iNode->Id] = microtime(true);

				$this->leader = microtime(true);
			}
			else if($this->state!="Leader" && microtime(true)>=$this->timeout)
			{
				echo "ID ".$this->MyProperties->Id." $this->state Started Election \n";
				$this->state = "Candidate";
				$this->currentTerm+=1;
				$this->votes = 0;
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
				$ids = array();
				for($i = max($this->matchIndex); $i>$this->commitIndex; $i-=1)
				{
					if( $this->log[$i]->term != $this->currentTerm)
						continue;
					$matchcount = 0;
					foreach ($this->matchIndex as $key => $value) {
						if( $value == $i)
						{
							$matchcount+=1;
							$ids[$key] = true;
						}
					}
					if($matchcount >= (int)((count($this->NodeList->Nodes)+1)/2))
					{
						echo "match count ";
						foreach ($ids as $key => $value) {
							echo $key;
						}
						echo "\n";
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

				
			
				$this->WriteLog();

				//echo "ID ".$this->MyProperties->Id." $this->state Applied ".$this->lastApplied." Term : ".$this->log[$this->lastApplied]->term." Command ".$this->log[$this->lastApplied]->command."\n";
			}

			if($this->state == "Candidate" && $this->votes >= (int)(count($this->NodeList)+1)/2)
			{
				echo "ID ".$this->MyProperties->Id." $this->state Won Election \n";
				$this->state = "Leader";
				$Message = new Message("AppendEntries", new AppendEntries($this->currentTerm,$this->MyProperties->Id, $this->lastLogIndex, $this->lastLogIndex>0?$this->log[$this->lastLogIndex]->term:null, null, $this->commitIndex));
				$this->Sender->BroadCastMessage($Message);
				$this->heartbeat = array(); 
				foreach ($this->NodeList->Nodes as $index => $iNode)
					$this->heartbeat[$iNode->Id] = microtime(true);

				$this->leader = microtime(true);
			}
			else if($this->state!="Leader" && microtime(true)>=$this->timeout)
			{
				echo "ID ".$this->MyProperties->Id." $this->state Started Election \n";
				$this->state = "Candidate";
				$this->currentTerm+=1;
				$this->votes = 0;
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
				$ids = array();
				for($i = max($this->matchIndex); $i>$this->commitIndex; $i-=1)
				{
					if( $this->log[$i]->term != $this->currentTerm)
						continue;
					$matchcount = 0;
					foreach ($this->matchIndex as $key => $value) {
						if( $value == $i)
						{
							$matchcount+=1;
							$ids[$i] = true;
						}
					}
					if($matchcount >= (int)((count($this->NodeList->Nodes)+1)/2))
					{
						echo "match count ";
						foreach ($ids as $key => $value) {
							echo $key;
						}
						echo "\n";
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
		$debug = "\n".time()."\t".$this->commitIndex."\t".$this->lastLogIndex."\t".$this->MyProperties->Id."\t";
		$content = "";
		foreach ($this->log as $id => $index)
			$content = $content.$index->command."\t";
		$content = $content."\n";
		$myFile = "Node ".$this->MyProperties->Id."/Debug.txt";
		$fh = fopen($myFile, 'a');
		fwrite($fh, $debug.$content);
		fclose($fh);
		$myFile = "Node ".$this->MyProperties->Id."/Latest.txt";
		$fh = fopen($myFile, 'w');
		fwrite($fh, $content);
		fclose($fh);
		echo($debug.$content);
	}

	public function NodeClose()
	{
		$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
	    socket_set_block($this->clientSocket);
	    socket_set_option($this->clientSocket, SOL_SOCKET, SO_LINGER, $arrOpt);
		socket_close($this->clientSocket);
		$this->clientSocket = false;
		$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
	    socket_set_block($this->socket);
	    socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, $arrOpt);
		socket_close($this->socket);
		$this->Reciever->CloseConnections();
		$this->Sender->CloseConnections();
		
		//echo "AS".$this->MyProperties->Id;
	}
}
