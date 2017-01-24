<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\Expression;
use PhpParser\NodeKind;
use PhpParser\Token;

class BracedExpression extends Expression {
    /** @var Token */
    public $openBrace;

    /** @var Expression */
    public $expression;

    /** @var Token */
    public $closeBrace;

}