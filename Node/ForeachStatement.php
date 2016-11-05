<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ForeachStatement extends StatementNode {
    /** @var Token */
    public $foreach;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $forEachCollectionName;
    /** @var Token */
    public $asKeyword;
    /** @var ForeachKey | null */
    public $foreachKey;
    /** @var ForeachValue */
    public $foreachValue;
    /**@var Token */
    public $closeParen;
    /** @var Token | null */
    public $colon;
    /**@var StatementNode | StatementNode[] */
    public $statements;
    /**@var Token | null */
    public $endForeach;
    /**@var Token | null */
    public $endForeachSemicolon;

    public function __construct() {
        parent::__construct(NodeKind::ForeachStatementNode);
    }
}