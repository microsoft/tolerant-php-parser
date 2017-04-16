<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Statement;

use Microsoft\PhpParser\Node\Name;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Node\TraitMembers;
use Microsoft\PhpParser\Token;

class TraitDeclaration extends StatementNode
{

    /** @var Token */
    public $traitKeyword;

    /** @var Name */
    public $name;

    /** @var TraitMembers */
    public $traitMembers;
}
