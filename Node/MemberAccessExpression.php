<?php
namespace PhpParser\Node;

use PhpParser\NodeKind;
use PhpParser\Token;

class MemberAccessExpression extends Expression {

    /** @var Expression */
    public $dereferencableExpression;

    /** @var Token */
    public $arrowToken;

    /** @var MemberName */
    public $memberName;

    public function __construct() {
        parent::__construct(NodeKind::MemberAccessExpression);
    }
}