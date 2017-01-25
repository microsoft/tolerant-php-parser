<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\Expression;
use PhpParser\Token;

class TernaryExpression extends Expression {

    /** @var Expression */
    public $condition;

    /** @var Token */
    public $questionToken;

    /** @var Expression */
    public $ifExpression;

    /** @var Token */
    public $colonToken;

    /** @var Expression */
    public $elseExpression;
}