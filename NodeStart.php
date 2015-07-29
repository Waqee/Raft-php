<?php
include 'Node.php';
include 'Messages/Message.php';


$x = array();
for ($a =0 ;$a<$argv[1];$a++)
	$x[] = new NodeProperties($a, "127.0.0.1", (int)$argv[2] + $a);


for ($i = 0; $i < $argv[1]; ++$i) {
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