<?php
/**
 * Created by PhpStorm.
 * User: Itani
 * Date: 11/22/2016
 * Time: 10:15 PM
 */

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