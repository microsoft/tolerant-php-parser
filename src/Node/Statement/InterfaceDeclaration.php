<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Statement;

use Microsoft\PhpParser\Node\InterfaceBaseClause;
use Microsoft\PhpParser\Node\InterfaceMembers;
use Microsoft\PhpParser\Node\Name;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Token;

class InterfaceDeclaration extends StatementNode
{

    /** @var Token */
    public $interfaceKeyword;

    /** @var Name */
    public $name;

    /** @var InterfaceBaseClause */
    public $interfaceBaseClause;

    /** @var InterfaceMembers */
    public $interfaceMembers;
}
