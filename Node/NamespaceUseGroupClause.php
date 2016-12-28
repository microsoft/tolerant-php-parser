<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\NodeKind;
use PhpParser\Token;

class NamespaceUseGroupClause extends Node {

    /** @var Token */
    public $functionOrConst;
    /** @var QualifiedName */
    public $namespaceName;
    /** @var  NamespaceAliasingClause */
    public $namespaceAliasingClause;

    public function __construct() {
        parent::__construct(NodeKind::NamespaceUseGroupClause);
    }
}