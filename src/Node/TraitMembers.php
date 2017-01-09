<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class TraitMembers extends Node {
    /** @var Token */
    public $openBrace;

    /** @var Node[] */
    public $traitMemberDeclarations;

    /** @var Token */
    public $closeBrace;

    public function __construct() {
        parent::__construct(NodeKind::TraitMembers);
    }
}