<?php

namespace Microsoft\PhpParser\Token;

class FunctionConstructor
{

    /**
     * @var string
     */
    private $beginKey;

    /**
     * @var string
     */
    private $endKey;

    /**
     * @var array
     */
    private $begin;

    /**
     * @var array
     */
    private $end;

    /**
     * @var string
     */
    private $last = '';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $tokenName;

    public function __construct(string $token, string $beginKey, string $endKey, string $prefix = 'is')
    {
        $this->edges = [];
        $this->beginKey = $beginKey;
        $this->endKey = $endKey;
        $this->prefix = $prefix;
        $this->name = $this->createFunctionName($token);
        $this->tokenName = str_replace($this->getKeyWords(), '', $token);
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function getKeyWords()
    {
        return [$this->beginKey, $this->endKey];
    }

    public function setValue(string $token, $value)
    {
        $this->checkCall($token);
        $this->last = $token;

        $keyword = trim(str_replace($this->tokenName, '', $token));
        switch ($keyword) {
            case $this->beginKey:
                $this->begin[] = $value;
                break;
            case $this->endKey:
                $this->end[] = $value;
                break;
            default:
                throw new Exception("Can't handle $keyword.");
        }
    }

    private function checkCall(string $token)
    {
        if (false === strpos($token, $this->tokenName)) {
            throw new Exception("'$token' mismatch function '{$this->name}'.");
        }

        if ($this->last === $token) {
            throw new Exception("Can't call '$token' twice.");
        }
    }

    private function createFunctionName(string $token): string
    {
        $token = str_replace($this->getKeyWords(), "", $token);
        $method = $this->prefix;
        foreach (explode('_', $token) as $tok) {
            $method .= ucfirst(strtolower($tok));
        }
        return $method;
    }

    public function isEndKeyword(string $token): bool
    {
        return $this->endKey . $this->tokenName === $token;
    }

    /**
     * 
     * @param Compiler $compiler
     * @param array $tokens array(value => name)
     */
    public function compile(Compiler $compiler, array $tokens)
    {
        $param = '$tokenKind';
        $compiler->write("public static function {$this->name}(int $param): bool\n")
                ->write("{\n")
                ->indent();
        $this->writeFunctionBody($param, $compiler, $tokens);
        $compiler->outdent()->write("}\n\n");
    }

    private function writeFunctionBody(string $param, Compiler $compiler, array $tokens)
    {
        $len = count($this->begin);
        if (count($this->begin) !== count($this->end)) {
            throw new Exception(sprintf("Mismatch begin with end edges in function %s related to %s.", $this->name, $this->tokenName));
        }
        if ($len === 0) {
            throw new Exception("Can't compile an empty function.");
        }
        $parts = [];
        for ($i = 0; $i < $len; $i++) {
            $parts[] = $this->getCondition($i, $param, $tokens);
        }
        $condition = implode(") || (", $parts);
        $condition = count($parts) > 1 ? "($condition)" : $condition;
        $compiler->write("return $condition;\n");
    }

    /**
     * 
     * @param int $i
     * @param string $param
     * @param array $tokens
     */
    private function getCondition(int $i, string $param, array $tokens): string
    {
        $begin = $this->begin[$i];
        $end = $this->end[$i];        
        $condition = "$begin < $param && $param < $end";
        
        $tokensInRange = $this->getTokensInRange($tokens, $begin, $end);
        
        if (count($tokensInRange) === 1) {
            $exactly = current($tokensInRange);
            $condition = "$exactly === $param";
        }
        
        return $condition;
    }

    /**
     * 
     * @param array $tokens
     * @param int $begin
     * @param int $end
     * @return array
     */
    private function getTokensInRange(array $tokens, int $begin, int $end): array
    {
        $inRange = [];
        foreach ($tokens as $tok => $const) {
            if ($begin < $const && $const < $end) {
                $inRange[$tok] = $const;
            }
        }
        return $inRange;
    }
}
