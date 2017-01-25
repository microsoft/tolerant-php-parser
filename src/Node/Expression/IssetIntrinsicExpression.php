<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\DelimitedList;
use PhpParser\Node\Expression;
use PhpParser\Token;

class IssetIntrinsicExpression extends Expression {

    /** @var Token */
    public $issetKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList\ExpressionList */
    public $expressions;

    /** @var Token */
    public $closeParen;

}