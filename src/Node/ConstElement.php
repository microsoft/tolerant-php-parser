<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\Token;

class ConstElement extends Node {

    /** @var Token */
    public $name;

    /** @var Token */
    public $equalsToken;

    /** @var Expression */
    public $assignment;

    public function getNodeKindName() : string {
        return 'ConstElement';
    }

}