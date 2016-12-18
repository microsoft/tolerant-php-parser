<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ClassNode extends Node {

    /** @var Token */
    public $abstractOrFinalModifier;

    /** @var Token */
    public $classKeyword;

    /** @var Name */
    public $name;

    /** @var ClassBaseClause */
    public $classBaseClause;

    /** @var ClassInterfaceClause */
    public $classInterfaceClause;

    /** @var ClassMembersNode */
    public $classMembers;

    public function __construct() {
        parent::__construct(NodeKind::ClassNode);
    }
}