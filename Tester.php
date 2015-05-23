<?php

error_reporting(E_ERROR | E_PARSE);


$port = $argv[1];

$server;

$pid;

function setupTest($no)
{
	global $port;
	global $pid;
	$pid = pcntl_fork();
	if (!$pid) 
	{
		shell_exec("php NodeStart.php $no $port > DebugLog.txt");
		exit(0);
	}

	sleep(2);

	$port+=(int)$no;
}

function connectNode($no)
{
	global $server;
	global $port;
	socket_write($server, "closesock", 100);
	echo "Connecting to Node $no\n";
	$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
    socket_set_block($server);
    socket_set_option($server, SOL_SOCKET, SO_LINGER, $arrOpt);
	socket_close($server);
	$server = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	socket_set_nonblock($server);
	$newport = (int)$port + (int)$no;
	while(socket_connect($server, "127.0.0.1", $newport)==false)
		;
}

function tryCommand($command)
{
	global $server;

	echo "Sending Command: $command\n";
	socket_write($server, $command, 100);

	if($command == "Sleep")
		return;
	else if($command == "quit")
	{
		$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
	    socket_set_block($server);
	    socket_set_option($server, SOL_SOCKET, SO_LINGER, $arrOpt);
		socket_close($server);
		return;
	}

	$t = time();

	while(true)
	{
		$res = socket_read($server, 20);
		if($res == $command)
		{
			echo "Command Applied $res\n";
			break;
		}
		else if(is_numeric($res))
		{
			echo "Replied with leader id $res\n";
			connectNode($res);
			tryCommand($command);
			break;
		}
		else if(time()> $t + 15)
		{
			echo "Resending command\n";
			connectNode(0);
			tryCommand($command);
			break;
		}
	}
}

function CloseAll($no)
{
	global $server;
	global $port;

	socket_write($server, "closesock", 100);
	$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
    socket_set_block($server);
    socket_set_option($server, SOL_SOCKET, SO_LINGER, $arrOpt);
	socket_close($server);
	for($i = 0; $i<$no; $i+=1)
	{
		$server = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
		$newport = (int)$port + (int)$i;
		if(socket_connect($server, "127.0.0.1", $newport)==true)
		{
			socket_write($server, "quit", 100);
		}
			$arrOpt = array('l_onoff' => 1, 'l_linger' => 1);
		    socket_set_block($server);
		    socket_set_option($server, SOL_SOCKET, SO_LINGER, $arrOpt);
			socket_close($server);
		
	}
}


function killProcessAndChilds($pid,$signal) {
        exec("ps -ef| awk '\$3 == '$pid' { print  \$2 }'", $output, $ret);
        if($ret) return 'you need ps, grep, and awk';
        while(list(,$t) = each($output)) {
            if ( $t != $pid ) {
                killProcessAndChilds($t,$signal);
            }
        }
        //echo "killing ".$pid."\n";
        posix_kill($pid, 9);
    } 

function TestResult($no)
{
	global $pid;
	global $server;
	global $port;
	$results = array();

	sleep(3);
	killProcessAndChilds($pid,9);
	sleep(1);

	for($i = 0; $i<$no; $i+=1)
	{
		$myfile = fopen("Node ".$i."/Latest.txt", "r") or die("Unable to open file!");
		$results[$i] = fread($myfile,filesize("Node ".$i."/Latest.txt"));
		fclose($myfile);
	}

	$same = true;

	echo "Log 0 : $results[0]\n";

	for($i = 1; $i<$no; $i+=1)
	{
		echo "Log $i : $results[$i]\n";
		if($results[$i]!=$results[0])
		{
			$same = false;
		}
	}	
}


// $pid = pcntl_fork();
// if (!$pid) 
// 	shell_exec("php NodeStart.php $no $port");

// sleep(2);

// $port += 5;

// socket_connect($server, "127.0.0.1", $port);

// echo "\nReady\n";

// while(true)
// {
// 	$command = fgets(STDIN);

// 	if($command == "con\n")
// 	{
// 		socket_close($server);
// 		$server = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
// 		socket_set_nonblock($server);
// 		$command = fgets(STDIN);
// 		$newport = (int)$port + (int)$command;
// 		socket_connect($server, "127.0.0.1", $newport);
// 	}
// 	else
// 	{
// 		if($command != "\n")
// 			socket_write($server, rtrim($command, "\n"), 100);
// 		if(rtrim($command, "\n") == "Sleep")
// 			continue;
// 		$res = socket_read($server, 20);

// 		if (strlen($res)!=0) {
		
// 			echo $res."\n";

// 			if($res == "Quit" || $res == "Exit")
// 			{
// 				socket_close($server);
// 				break;
// 			}
// 		}
// 	}
// }