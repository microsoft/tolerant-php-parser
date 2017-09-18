<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Expression;

use Microsoft\PhpParser\Node\Expression;
use Microsoft\PhpParser\Node\DelimitedList\ExpressionList;
use Microsoft\PhpParser\Token;

class EchoExpression extends Expression {

    /** @var Token */
    public $echoKeyword;

    /** @var ExpressionList */
    public $expressions;

    const CHILD_NAMES = [
        'echoKeyword',
        'expressions'
    ];
}
