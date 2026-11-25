<?php
// PHP 8.5+ allows #[Override] attribute on properties

class Base {
    protected string $value = 'base';
    protected int $count = 0;
}

class Extended extends Base {
    // Override attribute on property
    #[Override]
    protected string $value = 'extended';

    // Override with typed property
    #[Override]
    protected int $count = 10;
}

// Using property hooks with Override
class BaseWithHooks {
    protected string $name {
        get => 'base';
    }
}

class ExtendedWithHooks extends BaseWithHooks {
    #[Override]
    protected string $name {
        get => 'extended';
    }
}
