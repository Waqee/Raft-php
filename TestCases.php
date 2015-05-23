<?php 

include 'Tester.php';

function TestCase1()
{
	global $port;
	echo "\n\nTest Case 1 : 1 Log Test\nLog : Hello\n\n\n\n";
	setupTest(5);

	connectNode(0);

	tryCommand("Hello");

	sleep(1);

	CloseAll(5);

	TestResult(5);

	$port += 5;
}

function TestCase2()
{
	global $port;
	echo "\n\nTest Case 2 : Multiple Log Test\nLog : Hello How\n\n\n\n";
	setupTest(5);

	connectNode(0);

	tryCommand("Hello");

	sleep(1);

	tryCommand("How");

	sleep(1);

	tryCommand("Are");

	sleep(1);

	CloseAll(5);

	TestResult(5);

	$port += 5;
}

function TestCase3()
{
	global $port;
	echo "\n\nTest Case 3 : 2 Log Test with 1 node sleep 5 sec\nLog : Hello Good\n\n\n\n";
	setupTest(5);

	connectNode(0);

	tryCommand("Hello");

	sleep(1);

	tryCommand("Sleep");

	sleep(6);

	tryCommand("Good");

	sleep(1);

	CloseAll(5);

	TestResult(5);

	$port += 5;
}

function TestCase4()
{
	global $port;
	echo "\n\nTest Case 4 : 2 Log Test with 1 node shutdown\nLog : Hello Good\n\n\n\n";
	setupTest(5);

	connectNode(0);

	tryCommand("Hello");

	sleep(1);

	connectNode(0);

	tryCommand("quit");

	connectNode(1);

	sleep(1);

	tryCommand("Good");

	sleep(1);

	CloseAll(5);

	TestResult(5);

	$port += 5;
}

function TestCase5()
{
	global $port;
	echo "\n\nTest Case 5 : Multiple Log Test with 2 machines close\nLog : Hello How Are You Today\n\n\n\n";
	setupTest(5);

	connectNode(0);

	tryCommand("Hello");

	sleep(1);

	tryCommand("How");

	sleep(1);

	connectNode(0);

	tryCommand("quit");

	connectNode(1);

	sleep(1);

	tryCommand("Are");

	sleep(1);

	connectNode(2);

	tryCommand("quit");

	connectNode(1);

	sleep(1);

	tryCommand("You");

	sleep(1);

	tryCommand("Today");

	sleep(1);

	CloseAll(5);

	TestResult(5);

	$port += 5;
}
TestCase1();
TestCase2();
TestCase3();
TestCase4();
TestCase5();

?>