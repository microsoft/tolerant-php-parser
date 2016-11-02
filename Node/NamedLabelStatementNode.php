<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class NamedLabelStatementNode extends StatementNode {
    /** @var Token */
    public $name;
    /** @var Token */
    public $colon;
    /** @var StatementNode */
    public $statement;

    public function __construct() {
        parent::__construct(NodeKind::NamedLabelStatement);
    }
}