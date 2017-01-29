<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;

use PhpParser\Node\DelimitedList;
use PhpParser\Node\NamespaceAliasingClause;
use PhpParser\Node\QualifiedName;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

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

    public function getNodeKindName() : string {
        return 'NamespaceUseDeclaration';
    }

}