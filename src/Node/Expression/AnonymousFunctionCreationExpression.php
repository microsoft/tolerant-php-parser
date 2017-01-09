<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;
use PhpParser\Node\Expression;
use PhpParser\Node\FunctionBody;
use PhpParser\Node\FunctionHeader;
use PhpParser\Node\FunctionReturnType;
use PhpParser\Node\FunctionUseClause;
use PhpParser\NodeKind;
use PhpParser\Token;

class AnonymousFunctionCreationExpression extends Expression {
    /** @var Token | null */
    public $staticModifier;

    use FunctionHeader, FunctionUseClause, FunctionReturnType, FunctionBody;

    public function __construct() {
        parent::__construct(NodeKind::AnonymousFunctionCreationExpression);
    }
}