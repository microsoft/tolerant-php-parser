<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DelimitedList extends Node {
    /** @var Node[] */
    public $tokens;

    public function __construct() {
        parent::__construct(NodeKind::DelimitedList);
    }

    public function getValues() {
        $i = 0;
        foreach($this->tokens as $value) {
            if ($i++ % 2 == 1) {
                yield $value;
            }
        }
    }

    protected function addToken(Token $token) {
        array_push($this->tokens, $token);
    }
}