<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

$mod = 'Base_Print';
if (ModuleManager::is_installed($mod) < 0) { // not installed
    ModuleManager::install($mod, 0);
}
