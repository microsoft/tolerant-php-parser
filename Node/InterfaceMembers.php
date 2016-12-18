<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class InterfaceMembers extends Node {
    /** @var Token */
    public $openBrace;

    /** @var Node[] */
    public $interfaceMemberDeclarations;

    /** @var Token */
    public $closeBrace;

    public function __construct() {
        parent::__construct(NodeKind::InterfaceMembers);
    }
}