<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser\Node;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;
use Microsoft\PhpParser\Token;

class PropertyHook extends Node {
    /** @var AttributeGroup[]|null */
    public $attributes;
    /** @var Token */
    public $byRefToken;
    /** @var Token */
    public $name;
    /** @var Token|null */
    public $openParen;
    /** @var DelimitedList\ParameterDeclarationList|null */
    public $parameters;
    /** @var Token|null */
    public $closeParen;
    /** @var Token|null */
    public $arrowToken;
    /** @var CompoundStatementNode|Expression|Token|null */
    public $body;
    /** @var Token|null */
    public $semicolon;

    const CHILD_NAMES = [
        'attributes',
        'byRefToken',
        'name',
        'openParen',
        'parameters',
        'closeParen',
        'arrowToken',
        'body',
        'semicolon',
    ];
}
