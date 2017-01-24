<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\Expression;
use PhpParser\NodeKind;
use PhpParser\Token;

class SubscriptExpression extends Expression {

    /** @var Expression */
    public $postfixExpression;

    /** @var Token */
    public $openBracketOrBrace;

    public $accessExpression;

    /** @var Token */
    public $closeBracketOrBrace;
}