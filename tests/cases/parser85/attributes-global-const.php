<?php
// PHP 8.5+ allows attributes on global constants

#[Deprecated]
const SIMPLE_CONST = 1;

#[Deprecated("Use NEW_CONST instead")]
const OLD_CONST = 2;

#[SensitiveParameter]
const API_KEY = 'secret';

// Multiple attributes
#[Deprecated]
#[Internal]
const INTERNAL_CONST = 3;

// Attribute with multiple arguments
#[CustomAttribute(name: "test", version: 1)]
const CUSTOM_CONST = 4;
