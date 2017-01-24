<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class NamedLabelStatement extends StatementNode {
    /** @var Token */
    public $name;
    /** @var Token */
    public $colon;
    /** @var StatementNode */
    public $statement;

}