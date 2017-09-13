<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Token;

class TraitSelectOrAliasClause extends Node {
    /** @var QualifiedName|Node\Expression\ScopedPropertyAccessExpression */
    public $name;

    /** @var Token */
    public $asOrInsteadOfKeyword;

    /** @var Token[] */
    public $modifiers;

    /** @var QualifiedName|Node\Expression\ScopedPropertyAccessExpression */
    public $targetName;

    const CHILD_NAMES = [
        'name',
        'asOrInsteadOfKeyword',
        'modifiers',
        'targetName'
    ];
}
