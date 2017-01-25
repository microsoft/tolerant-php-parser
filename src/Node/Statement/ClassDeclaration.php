<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\ClassBaseClause;
use PhpParser\Node\ClassInterfaceClause;
use PhpParser\Node\ClassMembersNode;
use PhpParser\Node\Name;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class ClassDeclaration extends StatementNode {

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

    public function getNodeKindName() : string {
        return 'ClassDeclaration';
    }
}