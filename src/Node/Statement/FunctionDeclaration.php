<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;

use PhpParser\Node\FunctionBody;
use PhpParser\Node\FunctionHeader;
use PhpParser\Node\FunctionReturnType;
use PhpParser\Node\StatementNode;

class FunctionDeclaration extends StatementNode {
    use FunctionHeader, FunctionReturnType, FunctionBody;
}