<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;

class ArrayElement extends Node {

    /** @var Expression|null */
    public $elementKey;

    /** @var Token|null */
    public $arrowToken;

    /** @var Token|null */
    public $byRef;

    /** @var Expression */
    public $elementValue;

    const CHILD_NAMES = [
        'elementKey',
        'arrowToken',
        'byRef',
        'elementValue'
    ];
}
