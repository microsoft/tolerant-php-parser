<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ForStatement extends StatementNode {
    /** @var Token */
    public $for;
    /** @var Token */
    public $openParen;
    /** @var Expression | null */
    public $forInitializer;
    /** @var Token */
    public $exprGroupSemicolon1;
    /** @var Expression | null */
    public $forControl;
    /** @var Token */
    public $exprGroupSemicolon2;
    /** @var Expression | null */
    public $forEndOfLoop;
    /**@var Token */
    public $closeParen;
    /** @var Token | null */
    public $colon;
    /**@var StatementNode | StatementNode[] */
    public $statements;
    /**@var Token | null */
    public $endFor;
    /**@var Token | null */
    public $endForSemicolon;

    public function __construct() {
        parent::__construct(NodeKind::ForStatementNode);
    }
}