<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class UseVariableName extends Node {
    /** @var Token | null */
    public $byRef;

    /** @var Token */
    public $variableName;
}