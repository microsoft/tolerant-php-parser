<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class SourceFileNode extends Node {
    /** @var string */
    public $fileContents;

    /** @var Node[] */
    public $statementList;
    
    /** @var Token */
    public $endOfFileToken;

    public function __construct() {
        parent::__construct(NodeKind::SourceFileNode);
    }
}