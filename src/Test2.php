<?php

$server = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");

$port = 34305;

socket_connect($server, "127.0.0.1", $port);

while(true)
{
	$command = fgets(STDIN);

	if($command == "con\n")
	{
		socket_close($server);
		$server = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");

		$command = fgets(STDIN);
		$newport = (int)$port + (int)$command;
		socket_connect($server, "127.0.0.1", $newport);
	}
	else
	{

		socket_write($server, rtrim($command, "\n"), 100);
		if(rtrim($command, "\n") == "Sleep")
			continue;
		$res = socket_read($server, 20);

		echo $res."\n";

		if($res == "Quit")
		{
			socket_close($server);
			break;
		}
	}
}



// socket_write($server, "Hello", 100);

// $command = socket_read($server, 20);

// echo $command;

// if($command == 1 || $command == 2 ||$command == 3 || $command == 4 )
// {
// 	$server2 = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");

// 	socket_connect($server2, "127.0.0.1", $port + $command);
// 	socket_write($server2, "Hello", 100);

// 	$command = socket_read($server2, 20);

// 	echo $command;

// 	sleep (2);

// 	socket_write($server2, "Sleep", 100);

// 	$command = socket_read($server2, 20);

// 	echo $command;

// 	sleep (7);

// 	socket_write($server2, "Quit", 100);

// 	$command = socket_read($server2, 20);

// 	echo $command;

// 	socket_close($server2);
// }
// else
// {
// 	sleep (2);

// 	socket_write($server, "Sleep", 100);

// 	$command = socket_read($server, 20);

// 	echo $command;

// 	sleep (7);

// 	socket_write($server, "Quit", 100);

// 	$command = socket_read($server, 20);

// 	echo $command;
// }

// socket_close($server);