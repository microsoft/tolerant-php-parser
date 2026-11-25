<?php
// Verify attributes on class constants still work

class Example {
    #[Deprecated]
    public const SIMPLE = 1;

    #[Deprecated("Use NEW_VALUE instead")]
    protected const OLD_VALUE = 2;

    #[Internal]
    private const INTERNAL = 3;

    // Multiple attributes
    #[Deprecated]
    #[SensitiveParameter]
    public const SENSITIVE = 4;

    // Typed class constant with attribute
    #[CustomAttribute]
    public const int TYPED_CONST = 5;

    // Optional typed class constant with attribute
    #[CustomAttribute]
    public const ?int OPTIONAL_CONST = null;
}
