<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class InterfaceDeclaration extends Node {

    /** @var Token */
    public $interfaceKeyword;

    /** @var Name */
    public $name;

    /** @var InterfaceBaseClause */
    public $interfaceBaseClause;

    /** @var InterfaceMembersNode */
    public $interfaceMembers;

    public function __construct() {
        parent::__construct(NodeKind::InterfaceDeclaration);
    }
}