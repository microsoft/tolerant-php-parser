<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;

use PhpParser\Node\DelimitedList;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class ConstDeclaration extends StatementNode {

    /** @var Token */
    public $constKeyword;

    /** @var DelimitedList\ConstElementList */
    public $constElements;

    /** @var Token */
    public $semicolon;
}