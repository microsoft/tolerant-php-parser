<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class EmptyStatement extends StatementNode {
    /** @var Token */
    public $semicolon;

    public function getNodeKindName() : string {
        return 'EmptyStatement';
    }
}