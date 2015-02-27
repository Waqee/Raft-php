<?php
include 'Node.php';
include 'Messages/Message.php';

$x = array(new NodeProperties(1, "127.0.0.1", 21323), new NodeProperties(2, "127.0.0.1", 21324), new NodeProperties(3, "127.0.0.1", 21325));
$i=2;
            $b = new NodeList;
            foreach ($x as $key => $value) {
            	if($key!=$i)
            		$b->AddNode($value);
            }
            $c = new Node($x[$i], $b);
            $c->NodeStart();
        