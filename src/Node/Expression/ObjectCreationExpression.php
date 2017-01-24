<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Expression;

use PhpParser\Node\ClassBaseClause;
use PhpParser\Node\ClassInterfaceClause;
use PhpParser\Node\ClassMembersNode;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\Expression;
use PhpParser\Node\QualifiedName;

use PhpParser\NodeKind;
use PhpParser\Token;

class ObjectCreationExpression extends Expression {

    /** @var Token */
    public $newKeword;

    /** @var QualifiedName | Variable | Token */
    public $classTypeDesignator;

    /** @var Token | null */
    public $openParen;

    /** @var DelimitedList\ArgumentExpressionList | null  */
    public $argumentExpressionList;

    /** @var Token | null */
    public $closeParen;

    /** @var ClassBaseClause | null */
    public $classBaseClause;

    /** @var ClassInterfaceClause | null */
    public $classInterfaceClause;

    /** @var ClassMembersNode | null */
    public $classMembers;

}