<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class SwitchStatementNode extends StatementNode {
    /** @var Token */
    public $switchKeyword;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /** @var Token | null */
    public $colon;
    /** @var Token | null */
    public $openBrace;
    /**@var CaseStatementNode[] */
    public $caseStatements;
    /** @var Token | null */
    public $closeBrace;
    /**@var Token | null */
    public $endswitch;
    /**@var Token | null */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::SwitchStatementNode);
    }
}