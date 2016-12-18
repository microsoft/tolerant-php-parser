<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class IfStatementNode extends StatementNode {
    /** @var Token */
    public $ifKeyword;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /** @var Token | null */
    public $colon;
    /**@var StatementNode | StatementNode[] */
    public $statements;
    /** @var ElseIfClauseNode[] */
    public $elseIfClauses;
    /**@var ElseClauseNode | null */
    public $elseClause;
    /**@var Token | null */
    public $endifKeyword;
    /**@var Token | null */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::IfStatementNode);
    }
}