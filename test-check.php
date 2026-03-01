<?php

function myVoidFunc(): void {}

$res = match (true) {
    true => myVoidFunc(),
};

var_dump($res); // expecting NULL
