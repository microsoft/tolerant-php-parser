<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node\Statement;

use Microsoft\PhpParser\Node\DelimitedList;
use Microsoft\PhpParser\Node\NamespaceAliasingClause;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Token;

class NamespaceUseDeclaration extends StatementNode {
    /** @var Token */
    public $useKeyword;
    /** @var Token */
    public $functionOrConst;
    /** @var QualifiedName */
    public $namespaceName;
    /** @var NamespaceAliasingClause */
    public $namespaceAliasingClause;
    /** @var Token | null */
    public $openBrace;
    /** @var DelimitedList\NamespaceUseGroupClauseList | null */
    public $groupClauses;
    /** @var  Token | null */
    public $closeBrace;
    /** @var Token */
    public $semicolon;

}