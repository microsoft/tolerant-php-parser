<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ObjectCreationExpression extends Expression {

    /** @var Token */
    public $newKeword;

    /** @var QualifiedName | Variable | Token */
    public $classTypeDesignator;

    /** @var Token | null */
    public $openParen;

    /** @var DelimitedList | null  */
    public $argumentExpressionList;

    /** @var Token | null */
    public $closeParen;

    /** @var ClassBaseClause | null */
    public $classBaseClause;

    /** @var ClassInterfaceClause | null */
    public $classInterfaceClause;

    /** @var ClassMembersNode | null */
    public $classMembers;

    public function __construct() {
        parent::__construct(NodeKind::ObjectCreationExpression);
    }
}