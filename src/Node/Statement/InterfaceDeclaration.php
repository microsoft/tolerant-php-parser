<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\InterfaceBaseClause;
use PhpParser\Node\InterfaceMembers;
use PhpParser\Node\Name;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class InterfaceDeclaration extends StatementNode {

    /** @var Token */
    public $interfaceKeyword;

    /** @var Name */
    public $name;

    /** @var InterfaceBaseClause */
    public $interfaceBaseClause;

    /** @var InterfaceMembers */
    public $interfaceMembers;

}