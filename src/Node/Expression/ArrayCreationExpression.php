<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\DelimitedList;
use PhpParser\Node\Expression;
use PhpParser\NodeKind;
use PhpParser\Token;

class ArrayCreationExpression extends Expression {

    /** @var Token */
    public $arrayKeyword;

    /** @var Token */
    public $openParenOrBracket;

    /** @var DelimitedList\ArrayElementList */
    public $arrayElements;

    /** @var Token */
    public $closeParenOrBracket;

    public function __construct() {
        parent::__construct(NodeKind::ArrayCreationExpression);
    }
}