<?php

class Sender
{
	public $socket;

	public $clients;

	public function __construct($MyProperties)
	{
		set_time_limit(0);
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create socket\n");
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->socket, $MyProperties->ServerAddr, $MyProperties->PortNo);
		socket_listen($this->socket);
		socket_set_nonblock($this->socket);

		$this->client = array();
	}

	public function AcceptConnection($MyProperties, $no)
	{
		
		while (count($this->clients)!=$no) {
	        // create a copy, so $clients doesn't get modified by socket_select()
	        $read = array($this->socket);
	       
	        // get a list of all the clients that have data to be read from
	        // if there are no clients with data, go to next iteration
	        $ready=@socket_select($read, $write = NULL, $except = NULL,0);
		    if ($ready=== false)
		      die("Failed to listen for clients: ". socket_strerror(socket_last_error()));

		    // a client request service
		    elseif($ready>0){
		       
		            // accept the client, and add him to the $clients array
		            $newc = socket_accept($this->socket);
		           
		            echo "$MyProperties->Id B\n";
					$id = socket_read ($newc, 2);
				    echo "Client $id has connected to $MyProperties->Id\n";
				    $this->clients[$id] = $newc;
		           
	        }
	    }
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
