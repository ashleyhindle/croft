<?php

if (function_exists('arch')) {
    arch('it will not use debugging functions')
        ->expect(['dd', 'dump', 'ray'])
        ->each->not->toBeUsed();
}
