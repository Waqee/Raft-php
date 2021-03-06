<?php

class Reciever
{
	public $servers;

	public $MessageBuffers;

	public $status;

	public function __construct($NodeList)
	{ 

		$this->servers = array();
		$this->MessageBuffers = array();
		$this->status = array();
		set_time_limit(0);
		foreach($NodeList->Nodes as $Node)
		{
			echo $Node->Id;
			$this->servers[$Node->Id] = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
		}
	}

	public function TryConnections($MyProperties, $NodeList, $connectIndex)
	{
		foreach($NodeList->Nodes as $Node)
		{
			if($connectIndex == $Node->Id)
			{
				socket_connect($this->servers[$Node->Id], $Node->ServerAddr, $Node->PortNo);
				$this->status[$Node->Id] = true;
				socket_write($this->servers[$Node->Id], $MyProperties->Id, strlen ($MyProperties->Id) +1);
				echo "$MyProperties->Id has connected to Server $Node->Id ".count($this->status)."\n";
				$this->MessageBuffers[$Node->Id] = "";
				socket_set_nonblock($this->servers[$Node->Id]);
			}	
		}
	}

	public function TryRecieve()
	{
		$Messages = array();
		foreach($this->servers as $id=>$server)
		{
			if(array_key_exists($id,$this->status))
			{
				$this->MessageBuffers[$id] = $this->MessageBuffers[$id].socket_read ($server, 100);
				$ind = strpos($this->MessageBuffers[$id],"#");
				if($ind != false)
				{
					$output = substr($this->MessageBuffers[$id], 0, $ind);
					$str = json_decode($output);
					$Messages[] = $str;
					$this->MessageBuffers[$id] = substr($this->MessageBuffers[$id], $ind+1);
				}
			}
		}
		return $Messages;
	}

	public function CloseConnections()
	{
		foreach($this->servers as $server)
		{
			$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
		    socket_set_block($server);
		    socket_set_option($server, SOL_SOCKET, SO_LINGER, $arrOpt);
			socket_close($server);
		}
	}
}
