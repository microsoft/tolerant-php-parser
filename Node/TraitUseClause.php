<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class TraitUseClause extends Node {

    /** @var Token */
    public $useKeyword;

    /** @var DelimitedList */
    public $traitNameList;

    /** @var Token */
    public $semicolonOrOpenBrace;

    /** @var DelimitedList */
    public $traitSelectAndAliasClauses;

    /** @var Token */
    public $closeBrace;

    public function __construct() {
        parent::__construct(NodeKind::TraitUseClause);
    }
}