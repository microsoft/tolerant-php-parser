<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;

class QualifiedName extends Node
{
    /** @var Token */
    public $globalSpecifier; // \_opt
    /** @var Node */
    public $relativeSpecifier; // namespace\

    /** @var DelimitedList\QualifiedNameParts */
    public $nameParts;
}
