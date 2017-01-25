<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\Expression;

class UnaryExpression extends Expression  {
    /** @var UnaryExpression | Variable */
    public $operand;

    public function getNodeKindName() : string {
        return 'UnaryExpression';
    }
}