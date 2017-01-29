<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class CompoundStatementNode extends StatementNode {
    /** @var Token */
    public $openBrace;

    /** @var array | Node[] */
    public $statements;

    /** @var Token */
    public $closeBrace;

    public function getNodeKindName() : string {
        return 'CompoundStatementNode';
    }
}