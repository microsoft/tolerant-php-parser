<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\Token;

class TraitUseClause extends Node {

    /** @var Token */
    public $useKeyword;

    /** @var DelimitedList\QualifiedNameList */
    public $traitNameList;

    /** @var Token */
    public $semicolonOrOpenBrace;

    /** @var DelimitedList\TraitSelectOrAliasClauseList */
    public $traitSelectAndAliasClauses;

    /** @var Token */
    public $closeBrace;

    public function getNodeKindName() : string {
        return 'TraitUseClause';
    }

}