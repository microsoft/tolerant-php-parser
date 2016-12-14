<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ConstDeclaration extends StatementNode {

    /** @var Token */
    public $constKeyword;

    /** @var DelimitedList[] */
    public $constElements;

    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::ConstDeclaration);
    }
}