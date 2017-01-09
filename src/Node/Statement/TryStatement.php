<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\CatchClause;
use PhpParser\Node\FinallyClause;
use PhpParser\Node\StatementNode;
use PhpParser\NodeKind;
use PhpParser\Token;

class TryStatement extends StatementNode {
    /** @var Token */
    public $tryKeyword;
    /** @var StatementNode */
    public $compoundStatement;
    /** @var CatchClause[] | null */
    public $catchClauses;
    /**@var FinallyClause | null */
    public $finallyClause;

    public function __construct() {
        parent::__construct(NodeKind::IfStatementNode);
    }
}