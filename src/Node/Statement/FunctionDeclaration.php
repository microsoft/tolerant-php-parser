<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;

use PhpParser\Node;
use PhpParser\Node\FunctionBody;
use PhpParser\Node\FunctionHeader;
use PhpParser\Node\FunctionReturnType;
use PhpParser\Node\StatementNode;
use PhpParser\NodeKind;
use PhpParser\Token;

class FunctionDeclaration extends StatementNode {
    use FunctionHeader, FunctionReturnType, FunctionBody;

    public function __construct() {
        parent::__construct(NodeKind::FunctionDeclaration);
    }
}