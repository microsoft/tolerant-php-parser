<?php
trait A {
    use A {
        \A::one as b;
        \a\b\c::two insteadOf c;
        three as d;
    }
}