<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;
use PhpParser\TokenKind;

class ExpressionStatement extends StatementNode {
    /** @var Expression | null */
    public $expression;
    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::ExpressionStatement);
    }
}