<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Statement;

use Microsoft\PhpParser\Node\FunctionBody;
use Microsoft\PhpParser\Node\FunctionHeader;
use Microsoft\PhpParser\Node\FunctionReturnType;
use Microsoft\PhpParser\Node\StatementNode;

class FunctionDeclaration extends StatementNode
{
    use FunctionHeader, FunctionReturnType, FunctionBody;
}
