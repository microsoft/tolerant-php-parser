<?php

namespace Microsoft\PhpParser\Token;

class TokenCompiler {

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @var int
     */
    private $counter;

    /**
     * @var FunctionConstructor[]
     */
    private $functions;

    /**
     * @var int
     */
    private $lastValue;

    /**
     * @var callable
     */
    private $onNext = [];
    
    /**
     *
     * @var array 
     */
    private $tokens = [];

    private $keywords = ["begin_", "end_"];

    public function __construct(Compiler $compiler, int $counter = 0)
    {
        $this->compiler = $compiler;
        $this->counter = $counter;
    }

    public function compile(array $tokens): string
    {
        $namespace = 'Microsoft\PhpParser';
        $header = <<<EOD
<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace $namespace;

class TokenKind {\n
EOD;
        $this->compiler->raw($header);
        $this->compiler->indent();

        foreach ($tokens as $token) {

            $found = $this->checkWords($token, $this->keywords);

            if ($this->checkWord($token, "jump")) {
                $newCounter = (int) trim(str_replace('jump', '', $token));
                $this->counter = $newCounter;
                continue;
            }

            if ($this->checkWord($token, "TODO")) {
                $this->compiler->write("// $token\n");
                continue;
            }

            if ($this->checkWord($token, "comment")) {
                $token = trim(str_replace("comment", "", $token));
                $this->compiler->write("// $token\n");
                continue;
            }

            if ($found) {
                $this->addFunction($token, $this->lastValue);
                $this->compiler->write("// $token\n");
                continue;
            }

            $this->lastValue = $value = $this->generateValue();

            if ($this->onNext) {
                $this->callOnNext($token, $value);
            }
            $this->tokens[$token] = $value;
            $this->compiler->write("const $token = $value;\n");
        }
        
        $this->compiler->raw("\n");

        foreach ($this->functions as $func => $instance) {
            $instance->compile($this->compiler, $this->tokens);
        }

        $this->compiler->outdent();
        $this->compiler->write("}\n");
        return $this->compiler->getSource();
    }

    /**
     *
     * @param string $token
     * @param int $value
     * @return $this
     */
    private function addFunction(string $token, int $value)
    {
        $func = new FunctionConstructor($token, ...$this->keywords);
        $name = $func->getName();

        if (!isset($this->functions[$name])) {
            $this->functions[$name] = $func;
        } else {
            $func = $this->functions[$name];
        }

        if ($func->isEndKeyword($token)) {
            $this->addOnNext(function($tok, $val) use ($func, $token){
                $func->setValue($token, $val);
            });
            return $this;
        }

        $func->setValue($token, $value);

        return $this;
    }

    public function addOnNext(callable $func)
    {
        $this->onNext[] = $func;
        return $this;
    }

    public function callOnNext(string $token, $value)
    {
        foreach ($this->onNext as $func) {
            $func($token, $value);
        }
        $this->onNext = [];
        return $this;
    }

    /**
     * @return int
     */
    public function generateValue(): int
    {
        return $this->counter++;
    }

    private function checkWord(string $token, string $word) {
        if (false === strpos($token, $word)) {
            return false;
        }
        $this->compiler->raw("\n");
        return true;
    }

    private function checkWords(string $token, array $words)
    {
        foreach ($words as $word) {
            if ($this->checkWord($token, $word)) {
                return true;
            }
        }
        return false;
    }
}
