<?php

require_once __DIR__ . '/setup.php';

foreach (\glob(__DIR__ . '/Cases/*Test.phpt') as $file) {
	include $file;
}
