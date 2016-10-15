<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DelimitedList extends Node {
    /** @var Node[] */
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::DelimitedList);
        $this->children = array();
    }

    public function getValues() {
        $i = 0;
        foreach($this->children as $value) {
            if ($i++ % 2 == 1) {
                yield $value;
            }
        }
    }

    public function addToken($node) {
        array_push($this->children, $node);
    }
}