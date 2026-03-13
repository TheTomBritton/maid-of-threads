<?php

/**
 * ProcessWire Bootstrap
 *
 * This file serves as the entry point for all front-end requests.
 * It simply loads the ProcessWire boot loader.
 */

if(!defined('PROCESSWIRE')) define('PROCESSWIRE', true);

if(strpos(__FILE__, '..') !== false) die();

require(__DIR__ . '/wire/core/ProcessWire.php');
