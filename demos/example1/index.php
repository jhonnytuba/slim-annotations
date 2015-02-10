<?php
require '../../vendor/autoload.php';

use \SlimAnnotation\Mapping\Driver\SlimAnnotationDriver;

SlimAnnotationDriver::newInstance(array(__DIR__.'/src/'))
	->runAnnotations();