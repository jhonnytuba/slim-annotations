<?php
require '../../vendor/autoload.php';

use \SlimAnnotation\Mapping\Driver\SlimAnnotationDriver;

$app = SlimAnnotationDriver::newInstance(array(__DIR__.'/src/'))
	->createAppWithAnnotations();

$app->run();