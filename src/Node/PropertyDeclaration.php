<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;

class PropertyDeclaration extends Node
{

    /** @var Token[] */
    public $modifiers;

    /** @var DelimitedList\ExpressionList */
    public $propertyElements;

    /** @var Token */
    public $semicolon;
}
