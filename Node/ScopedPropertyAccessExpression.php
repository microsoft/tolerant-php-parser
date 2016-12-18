<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\NodeKind;
use PhpParser\Token;

class ScopedPropertyAccessExpression extends Expression {

    /** @var Expression | QualifiedName | Token */
    public $scopeResolutionQualifier;

    /** @var Token */
    public $doubleColon;

    /** @var Token | Variable */
    public $memberName;

    public function __construct() {
        parent::__construct(NodeKind::ScopedPropertyAccessExpression);
    }
}