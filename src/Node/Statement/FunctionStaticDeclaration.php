<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class FunctionStaticDeclaration extends StatementNode {

    /** @var Token */
    public $staticKeyword;

    /** @var DelimitedList\ParameterDeclarationList */
    public $staticVariableNameList;

    /** @var Token */
    public $semicolon;

}