<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\Expression;
use PhpParser\NodeKind;
use PhpParser\Token;

class ExitIntrinsicExpression extends Expression {

    /** @var Token */
    public $exitOrDieKeyword;

    /** @var Token | null */
    public $openParen;

    /** @var Expression | null */
    public $expression;

    /** @var Token | null */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::ExitIntrinsicExpression);
    }
}