<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\DelimitedList;
use PhpParser\Node\Expression;
use PhpParser\Token;

class ListIntrinsicExpression extends Expression {

    /** @var Token */
    public $listKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList\ListExpressionList */
    public $listElements;

    /** @var Token */
    public $closeParen;

    public function getNodeKindName() : string {
        return 'ListIntrinsicExpression';
    }

}