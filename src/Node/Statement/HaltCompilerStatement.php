<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Statement;

use Microsoft\PhpParser\Node\DelimitedList;
use Microsoft\PhpParser\Node\Expression;
use Microsoft\PhpParser\Token;

class HaltCompilerStatement extends Expression {

    /** @var Token */
    public $haltCompilerKeyword;

    /** @var Token */
    public $openParen;

    /** @var Token */
    public $closeParen;

    /** @var Token */
    public $semicolon;

    /** @var Token */
    public $data;

    const CHILD_NAMES = [
        'haltCompilerKeyword',
        'openParen',
        'closeParen',
        'semicolon',
        'data',
    ];
}
