<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;
use PhpParser\Node\Expression;
use PhpParser\NodeKind;
use PhpParser\Token;

class ScriptInclusionExpression extends Expression  {
    /** @var Token */
    public $requireOrIncludeKeyword;
    /** @var Expression */
    public $expression;

    public function __construct() {
        parent::__construct(NodeKind::ScriptInclusionExpression);
    }
}