<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\ElseClauseNode;
use PhpParser\Node\ElseIfClauseNode;
use PhpParser\Node\Expression;
use PhpParser\Node\StatementNode;
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

    public function getNodeKindName() : string {
        return 'IfStatementNode';
    }

}