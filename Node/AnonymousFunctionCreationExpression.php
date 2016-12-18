<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class AnonymousFunctionCreationExpression extends  Node {
    /** @var Token | null */
    public $staticModifier;

    use FunctionHeader, FunctionUseClause, FunctionReturnType, FunctionBody;

    public function __construct() {
        parent::__construct(NodeKind::AnonymousFunctionCreationExpression);
    }
}