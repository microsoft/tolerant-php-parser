<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\Token;

class DefaultStatementNode extends Node {
    /** @var Token */
    public $defaultKeyword;
    /** @var Token */
    public $defaultLabelTerminator;
    /**@var StatementNode[] */
    public $statementList;

}