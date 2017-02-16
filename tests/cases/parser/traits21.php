<?php
trait A {
    use A {
        \A as b;
        b insteadOf c;
    }
}