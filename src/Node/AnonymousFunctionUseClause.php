<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\Node\DelimitedList\UseVariableNameList;
use PhpParser\NodeKind;
use PhpParser\Token;

class AnonymousFunctionUseClause extends Node {
    /** @var Token */
    public $useKeyword;

    /** @var Token */
    public $openParen;

    /** @var UseVariableNameList */
    public $useVariableNameList;

    /** @var Token */
    public $closeParen;

}