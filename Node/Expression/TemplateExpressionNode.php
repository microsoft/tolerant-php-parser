<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression
use PhpParser\NodeKind;
use PhpParser\Token;

class TemplateExpressionNode extends Node {
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::TemplateExpression);
    }
}