<?php
include 'Node.php';
include 'Messages/Message.php';

$x = array();
for ($a =0 ;$a<5;$a++)
	$x[] = new NodeProperties($a, "127.0.0.1", 35160 + $a);

function errHandle($errNo, $errStr, $errFile, $errLine) { 
    $msg = "$errStr in $errFile on line $errLine";
    if ($errNo == E_NOTICE || $errNo == E_WARNING) {
        throw new ErrorException($msg, $errNo);
    } else {
        echo $msg;
    }
}

for ($i = 0; $i < 5; ++$i) {
        $pid = pcntl_fork();

        if (!$pid) {

            $b = new NodeList;
            foreach ($x as $key => $value) {
            	if($key!=$i)
            		$b->AddNode($value);
            }
            $c = new Node($x[$i], $b);
            $c->NodeStart();
            exit($i);
        }
    }

    while (pcntl_waitpid(0, $status) != -1) {
        $status = pcntl_wexitstatus($status);
        echo "Child $status completed\n";
    }