<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\PropertyHooks;
use Microsoft\PhpParser\Token;

class PropertyHooks extends Node {
    /** @var Token */
    public $openBrace;

    /** @var PropertyHook[] */
    public $hookDeclarations;

    /** @var Token */
    public $closeBrace;

    const CHILD_NAMES = [
        'openBrace',
        'hookDeclarations',
        'closeBrace'
    ];
}
