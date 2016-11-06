<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DeclareStatement extends StatementNode {
    /** @var Token */
    public $declareKeyword;
    /** @var Token */
    public $openParen;
    /** @var Node */
    public $declareDirective;
    /** @var Token */
    public $closeParen;
    /** @var Token | null */
    public $colon;
    /** @var StatementNode | StatementNode[] */
    public $statements;
    /** @var Token | null */
    public $enddeclareKeyword;
    /** @var Token | null */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::DeclareStatementNode);
    }
}