<?php

$server = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");

$port = 32605;

socket_connect($server, "127.0.0.1", $port);

socket_write($server, "Hello", 100);

$command = socket_read($server, 20);

echo $command;

if($command == 1 || $command == 2 ||$command == 3 || $command == 4 )
{
	$server2 = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");

	socket_connect($server2, "127.0.0.1", $port + $command);
	socket_write($server2, "Hello", 100);

	$command = socket_read($server2, 20);

	echo $command;

	sleep (2);

	socket_write($server2, "Now", 100);

	$command = socket_read($server2, 20);

	echo $command;
}

