<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ReservedWord extends Expression {
    /** @var Token[] */
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::ReservedWord);
    }
}