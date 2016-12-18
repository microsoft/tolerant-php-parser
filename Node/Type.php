<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Parameter extends Node {
    /** @var Token */
    public $type;
    /** @var Token */
    public $byRefToken;
    /** @var Token */
    public $variableName;
    /** @var Token */
    public $openParen;
    /** @var DelimitedList */
    public $parameters;
    /** @var Token */
    public $closeParen;
    /** @var Type */
    public $returnTypeOpt;

    public function __construct() {
        parent::__construct(NodeKind::FunctionNode);
    }
}