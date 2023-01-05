<?php

require __DIR__ . '/vendor/autoload.php';

$engine = new Latte\Engine;
$engine->addExtension(new \Nette\Bridges\ApplicationLatte\UIExtension(null));
$engine->addExtension(new \Nette\Bridges\FormsLatte\FormsExtension());
$engine->addExtension(new Latte\Essential\TranslatorExtension(fn($val) => $val));

$linter = new Latte\Tools\Linter($engine);
$ok = $linter->scanDirectory('src');
exit($ok ? 0 : 1);