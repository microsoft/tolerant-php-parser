<?php

namespace Microsoft\PhpParser\Token;

/**
 * Helps to compile PHP code
 * Borrowed from Twig's compiler
 */
class Compiler {
    
    /**
     * @var int
     */
    private $indentation;

    /**
     * @var string
     */
    private $source;


    public function __construct(int $indentation = 0)
    {
        $this->indentation = $indentation;

        $this->source = '';

    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    public function compile(array $tokens, int $indentation = 0)
    {
    }

        /**
     * Adds a raw string to the compiled code.
     *
     * @param string $string The string
     *
     * @return $this
     */
    public function raw($string)
    {
        $this->source .= $string;

        return $this;
    }

    public function writeLine(...$strings)
    {
        $this->write($strings);
        $this->source .= "\n";
        return $this;
    }

    public function write(...$strings)
    {
        foreach ($strings as $string) {
            $this->source .= str_repeat(' ', $this->indentation * 4) . $string;
        }
        return $this;
    }

    /**
     * Adds a quoted string to the compiled code.
     *
     * @param string $value The string
     *
     * @return $this
     */
    public function string($value)
    {
        $this->source .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));

        return $this;
    }

    /**
     * Returns a PHP representation of a given value.
     *
     * @param mixed $value The value to convert
     *
     * @return $this
     */
    public function repr($value)
    {
        if (\is_int($value) || \is_float($value)) {
            if (false !== $locale = setlocale(LC_NUMERIC, '0')) {
                setlocale(LC_NUMERIC, 'C');
            }

            $this->raw(var_export($value, true));

            if (false !== $locale) {
                setlocale(LC_NUMERIC, $locale);
            }
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (\is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (\is_array($value)) {
            $this->raw('array(');
            $first = true;
            foreach ($value as $key => $v) {
                if (!$first) {
                    $this->raw(', ');
                }
                $first = false;
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($v);
            }
            $this->raw(')');
        } else {
            $this->string($value);
        }

        return $this;
    }

    /**
     * Indents the generated code.
     *
     * @param int $step The number of indentation to add
     *
     * @return $this
     */
    public function indent($step = 1)
    {
        $this->indentation += $step;

        return $this;
    }

    /**
     * Outdents the generated code.
     *
     * @param int $step The number of indentation to remove
     *
     * @return $this
     *
     * @throws \LogicException When trying to outdent too much so the indentation would become negative
     */
    public function outdent($step = 1)
    {
        // can't outdent by more steps than the current indentation level
        if ($this->indentation < $step) {
            throw new \LogicException('Unable to call outdent() as the indentation would become negative.');
        }

        $this->indentation -= $step;

        return $this;
    }
}