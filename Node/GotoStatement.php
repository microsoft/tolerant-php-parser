<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class GotoStatement extends StatementNode {
    /** @var Token */
    public $goto;
    /** @var Token */
    public $name;
    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::GotoStatementNode);
    }
}