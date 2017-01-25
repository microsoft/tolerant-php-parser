<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\Token;

class InterfaceBaseClause extends Node {
    /** @var Token */
    public $extendsKeyword;

    /** @var DelimitedList\QualifiedNameList */
    public $interfaceNameList;

    public function getNodeKindName() : string {
        return 'InterfaceBaseClause';
    }

}