<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class AnonymousFunctionUseClause extends Node {
    /** @var Token */
    public $useKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList */
    public $useVariableNameList;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::AnonymousFunctionUseClause);
    }
}