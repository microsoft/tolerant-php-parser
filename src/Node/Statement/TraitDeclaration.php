<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;

use PhpParser\Node\Name;
use PhpParser\Node\StatementNode;
use PhpParser\Node\TraitMembers;
use PhpParser\Token;

class TraitDeclaration extends StatementNode {

    /** @var Token */
    public $traitKeyword;

    /** @var Name */
    public $name;

    /** @var TraitMembers */
    public $traitMembers;

}