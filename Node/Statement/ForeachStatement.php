<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\Expression;
use PhpParser\Node\ForeachKey;
use PhpParser\Node\ForeachValue;
use PhpParser\Node\StatementNode;
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