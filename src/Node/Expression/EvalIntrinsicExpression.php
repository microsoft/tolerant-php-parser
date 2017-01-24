<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\Expression;
use PhpParser\Token;

class EvalIntrinsicExpression extends Expression {

    /** @var Token */
    public $evalKeyword;

    /** @var Token */
    public $openParen;

    /** @var Expression */
    public $expression;

    /** @var Token */
    public $closeParen;

}