<?php

// TODO technically should fail
function gen() {
    yield from 1 => 2;
}