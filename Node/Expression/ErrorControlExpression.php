<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\NodeKind;
use PhpParser\Token;

class ErrorControlExpression extends UnaryExpression {

    /** @var Token */
    public $operator;

    /** @var UnaryExpression */
    public $operand;

    public function __construct() {
        parent::__construct(NodeKind::ErrorControlExpression);
    }
}