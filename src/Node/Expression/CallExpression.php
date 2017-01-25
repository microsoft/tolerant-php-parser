<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\Expression;
use PhpParser\Token;

class CallExpression extends Expression {
    /** @var Expression */
    public $callableExpression;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList\ArgumentExpressionList | null */
    public $argumentExpressionList;

    /** @var Token */
    public $closeParen;

    public function getNodeKindName() : string {
        return 'CallExpression';
    }

}