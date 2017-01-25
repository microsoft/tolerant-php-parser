<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;

use PhpParser\Node;
use PhpParser\Node\StatementNode;
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

    public function getNodeKindName() : string {
        return 'DeclareStatement';
    }
}