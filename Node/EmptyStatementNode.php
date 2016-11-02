<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class EmptyStatementNode extends StatementNode {
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::EmptyStatement);
    }
}