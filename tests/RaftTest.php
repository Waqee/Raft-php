<?php
class MoneyTest extends PHPUnit_Framework_TestCase
{
    // ...

    public function testCanBeNegated()
    {
        // Arrange
        $a = new NodeProperties(1, 2, 3);

        // Act
        $b = new NodeList($a);

	$b->AddNode($a);

	$c = new Node($a, $b);

        // Assert
        $this->assertEquals(2, $c->NodeList->Nodes[0]->NodeServerAddr);
    }

    // ...
}
