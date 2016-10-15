<?php

namespace PhpParser\Node;

//require_once (__DIR__ . "/../NodeKind.php");

use PhpParser\Token;

class Node implements \JsonSerializable {
    public $kind;
    public $parent;

    public function __construct(int $kind) {
        $this->kind = $kind;
    }

    public function getLength() {
        $length = 0;

        foreach ($this->getChildren() as $child) {
            if ($child instanceof Node) {
                $length += $child->getLength();
            } else if ($child instanceof Token) {
                $length += $child->length;
            }
        }
        return $length;
    }

    public function getAllChildren() {
        $allChildren = array();

        foreach ($this->getChildren() as $child) {
            if ($child instanceof Node) {
                array_push($allChildren, $child);
                foreach ($child->getAllChildren() as $subChild) {
                    array_push($allChildren, $subChild);
                }
            } else if ($child instanceof Token) {
                array_push($allChildren, $child);
            }
        }
        return $allChildren;
    }

    private function getChildren() {
        $result = array();
        foreach (call_user_func('get_object_vars', $this) as $i=>$val) {
            if ($i === "parent" || $i == "kind") {
                continue;
            }
            if (is_array($val)) {
                foreach ($val as $child) {
                    array_push($result, $child);
                }
                continue;
            }
            array_push($result, $val);
        }
        return $result;
    }

    public function getStart() {
        $child = $this->getChildren()[0];
        if ($child instanceof Node) {
            return $child->getStart();
        } else if ($child instanceof Token) {
            return $child->start;
        }
        throw new \Exception("Unknown type in AST");
    }

    public function jsonSerialize() {
        $constants = (new \ReflectionClass("PhpParser\\NodeKind"))->getConstants();
        $kindName = $this->kind;
        foreach ($constants as $name=>$val) {
            if ($val == $this->kind) {
                $kindName = $name;
            }
        }

        return ["$kindName" => $this->getChildren()];
    }
}