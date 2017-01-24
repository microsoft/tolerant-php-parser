<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class QualifiedName extends Node {
    /** @var Token */
    public $globalSpecifier; // \_opt
    /** @var Node */
    public $relativeSpecifier; // namespace\

    /** @var DelimitedList\QualifiedNameParts */
    public $nameParts;

}