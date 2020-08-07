<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Statement;

use Microsoft\PhpParser\Node\DelimitedList\DeclareDirectiveList;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Token;

class DeclareStatement extends StatementNode {
    /** @var Token */
    public $declareKeyword;
    /** @var Token */
    public $openParen;
    /** @var DeclareDirectiveList */
    public $declareDirectives;
    /** @var Token */
    public $closeParen;
    /** @var Token|null */
    public $colon;
    /** @var StatementNode|StatementNode[] */
    public $statements;
    /** @var Token|null */
    public $enddeclareKeyword;
    /** @var Token|null */
    public $semicolon;

    const CHILD_NAMES = [
        'declareKeyword',
        'openParen',
        'declareDirectives',
        'closeParen',
        'colon',
        'statements',
        'enddeclareKeyword',
        'semicolon'
    ];
}
