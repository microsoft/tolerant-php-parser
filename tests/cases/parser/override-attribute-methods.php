<?php
// PHP 8.3+ #[\Override] attribute on methods

class BaseClass {
    public function baseMethod(): void {}

    protected function protectedMethod(): string {
        return 'base';
    }

    public static function staticMethod(): int {
        return 1;
    }
}

class ChildClass extends BaseClass {
    // Override on public method
    #[\Override]
    public function baseMethod(): void {
        // implementation
    }

    // Override on protected method
    #[\Override]
    protected function protectedMethod(): string {
        return 'child';
    }

    // Override on static method
    #[\Override]
    public static function staticMethod(): int {
        return 2;
    }

    // Multiple attributes including Override
    #[\Override]
    #[\Deprecated]
    public function deprecatedOverride(): void {}
}

// Override in interface implementation
interface TestInterface {
    public function interfaceMethod(): void;
}

class ImplementingClass implements TestInterface {
    #[\Override]
    public function interfaceMethod(): void {
        // implementation
    }
}

// Override with abstract methods
abstract class AbstractBase {
    abstract public function abstractMethod(): void;
}

class ConcreteClass extends AbstractBase {
    #[\Override]
    public function abstractMethod(): void {
        // implementation
    }
}
