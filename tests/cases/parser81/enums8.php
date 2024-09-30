<?php
class X {
    // Cases are only allowed in enums. But this is a compile-time error, not a parse error
    case F = 1;
}
