<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Token;

class ReservedWord extends Expression {
    /** @var Token[] */
    public $children;

    public function getNodeKindName() : string {
        return 'ReservedWord';
    }

}