<?php

namespace Zcgo\Lib;

use danog\ClassFinder\ClassFinder;

class CommandLister
{
    public static function list()
    {
        ClassFinder::setAppRoot('./');
        $classes = ClassFinder::getClassesInNamespace('Zcgo\Commands');
        return $classes;
    }
}
