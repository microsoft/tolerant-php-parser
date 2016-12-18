<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\NodeKind;
use PhpParser\Token;

class ScriptSection extends Node {
    /** @var Token */
    public $text;
    /** @var Token */
    public $startTag;
    /** @var Node[] */
    public $statementList;
    /** @var Token */
    public $endTag;

    public function __construct() {
        parent::__construct(NodeKind::ScriptSection);
    }
}

