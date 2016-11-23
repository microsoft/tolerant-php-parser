<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class NamespaceDefinition extends StatementNode {
    /** @var Token */
    public $namespaceKeyword;
    /** @var QualifiedName | null */
    public $name;
    /** @var Expression | Token */
    public $compoundStatementOrSemicolon;

    public function __construct() {
        parent::__construct(NodeKind::NamespaceDefinition);
    }
}