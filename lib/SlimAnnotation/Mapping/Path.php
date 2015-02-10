<?php
namespace SlimAnnotation\Mapping;

use \Slim\Slim;

abstract class Path {
	
	protected $app;
	
	public function __construct(Slim $app) {
		$this->app = $app;
	}
	
}