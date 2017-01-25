<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;


use PhpParser\Token;

class PrefixUpdateExpression extends UnaryExpression {

    /** @var Token */
    public $incrementOrDecrementOperator;

    /** @var Variable */
    public $operand;

}