<?php

class Sender
{
	public $socket;

	public $clients;

	public function __construct($MyProperties)
	{
		set_time_limit(0);
		$this->socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
		socket_bind($this->socket, $MyProperties->ServerAddr, $MyProperties->PortNo);
		socket_listen($this->socket);
		socket_set_nonblock($this->socket);

		$this->client = array();
	}

	public function AcceptConnection($MyProperties, $no)
	{
		$time_pre = microtime(true);
		$timer = (mt_rand(0,3)==0)?1/100:0;
		while(microtime(true)-$time_pre<$timer)
		{
			if(($newc = socket_accept($this->socket)) !== false)
			{
				echo "$MyProperties->Id B\n";
				$id = socket_read ($newc, 2);
			    echo "Client $id has connected to $MyProperties->Id\n";
			    $this->clients[$id] = $newc;
			}
		}
		return count($this->clients);
	}

	public function SendMessage($id, $Message)
	{

		$str = json_encode($Message)."#";
		socket_write($this->clients[$id], $str, strlen ($str)+1);
	}

	public function BroadCastMessage($Message)
	{

		$str = json_encode($Message)."#";
		foreach($this->clients as $client)
			socket_write($client, $str, strlen ($str));
	}

	public function CloseConnections()
	{
		foreach($this->clients as $client)
		{
			$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
		    socket_set_block($client);
		    socket_set_option($client, SOL_SOCKET, SO_LINGER, $arrOpt);
			socket_close($client);
		}
		$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
	    socket_set_block($this->socket);
	    socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, $arrOpt);
    
		socket_close($this->socket);

	}
		
}
