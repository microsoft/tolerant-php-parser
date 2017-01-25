<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\Token;

class StaticVariableDeclaration extends Node {

    /** @var Token */
    public $variableName;

    /** @var Token | null */
    public $equalsToken;

    /** @var Expression | null */
    public $assignment;

    public function getNodeKindName() : string {
        return 'StaticVariableDeclaration';
    }

}