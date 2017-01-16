<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Parameter extends Node {
    /** @var QualifiedName | Token | null */
    public $typeDeclaration;
    /** @var Token | null */
    public $byRefToken;
    /** @var Token | null */
    public $dotDotDotToken;

    /** @var Token */
    public $variableName;

    /** @var Token | null */
    public $equalsToken;

    /** @var null | Expression */
    public $default;


    public function __construct() {
        parent::__construct(NodeKind::Parameter);
    }

    public function isVariadic() {

    }
}