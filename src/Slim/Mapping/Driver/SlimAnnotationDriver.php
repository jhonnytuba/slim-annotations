<?php
namespace SlimAnnotation\Mapping\Driver;

use \ReflectionClass;
use \ReflectionMethod;
use \Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use \Doctrine\Common\Persistence\Mapping\ClassMetadata;
use \Doctrine\Common\Annotations\SimpleAnnotationReader;
use \Doctrine\Common\Annotations\CachedReader;
use \Doctrine\Common\Annotations\AnnotationRegistry;
use \Doctrine\Common\Cache\ArrayCache;
use \Slim\Slim;
use \SlimAnnotation\Mapping\Annotation\ApplicationPath;
use \SlimAnnotation\Mapping\Annotation\Path;

class SlimAnnotationDriver extends AnnotationDriver {
	
	protected $entityAnnotationClasses = array(
		'SlimAnnotation\Mapping\Annotation\ApplicationPath' => 1,
		'SlimAnnotation\Mapping\Annotation\Path' => 2,
		'SlimAnnotation\Mapping\Annotation\Middleware' => 3,
	);
	
	private function __construct($reader, $paths = null) {
		parent::__construct($reader, $paths);
	}
	
	private function loadMetadataForClass($className, ClassMetadata $metadata) {
	}
	
	public function loadAnnotationsForApp() {
		$app = new Slim();
		
		foreach ($this->getAllClassNames() as $className) {
			$class = new ReflectionClass($className);
			$classAnnotations = $this->getClassAnnotations($class);
			
			if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\ApplicationPath'])) {
				$this->loadApplicationPath($app, $classAnnotations['SlimAnnotation\Mapping\Annotation\ApplicationPath']);
			}
			else if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Middleware'])) {
				$this->loadMiddleware($app, $class);
			}
			else if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Path'])) {
				$this->loadPath($app, $class, $classAnnotations['SlimAnnotation\Mapping\Annotation\Path']);
			}
		}
	}
	
	private function loadApplicationPath(Slim $app, ApplicationPath $classAnnotation) {
		if ($classAnnotation->responseContentType) {
			$app->response()->header($classAnnotation->responseContentType);
		}
	}
	
	private function loadMiddleware(Slim $app, ReflectionClass $class) {
		$app->add($class->newInstance());
	}
	
	private function loadPath(Slim $app, ReflectionClass $class, Path $pathAnnotation) {
		foreach ($class->getMethods() as $method) {
			$instanceClass = $class->newInstance($app);
			$methodAnnotations = getMethodAnnotations($method);
				
			$uri = $this->normalizeURI($pathAnnotation->uri, $methodAnnotations['SlimAnnotation\Mapping\Annotation\Path']);
			
			if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\POST'])) {
				$app->post($uri, $method->invoke($instanceClass));
			}
			else if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\GET'])) {
				$app->get($uri, $method->invoke($instanceClass));
			}
			else if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\DELETE'])) {
				$app->delete($uri, $method->invoke($instanceClass));
			}
			else if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\PUT'])) {
				$app->put($uri, $method->invoke($instanceClass));
			}
		}
	}
	
	private function normalizeURI($uriClass, $uriMethod) {
		$uri = $uriClass;
		
		if (substr($uri, 0, 1) != '/') {
			$uri = '/' . $uri;
		}

		if (substr($uri, -1, 1) != '/') {
			$uri .= '/';
		}
		
		if ($uriMethod) {
			if (substr($uriMethod, 0, 1) == '/') {
				$uri .= substr($uriMethod, 1);
			}
			else {
				$uri .= $uriMethod;
			}
			
			if (substr($uri, -1, 1) != '/') {
				$uri .= '/';
			}
		}
		return $uri;
	}
	
	private function getClassAnnotations(ReflectionClass $class) {
		$classAnnotations = $this->reader->getClassAnnotations($class);
		
		if ($classAnnotations) {
			foreach ($classAnnotations as $key => $annot) {
				if ( ! is_numeric($key)) {
					continue;
				}
				$classAnnotations[get_class($annot)] = $annot;
			}
		}
		
		return $classAnnotations;
	}
	
	private function getMethodAnnotations(ReflectionMethod $method) {
		$methodAnnotations = $this->reader->getMethodAnnotations($method);
			
		foreach ($methodAnnotations as $key => $annot) {
			if ( ! is_numeric($key)) {
				continue;
			}
			$methodAnnotations[get_class($annot)] = $annot;
		}
		
		return $methodAnnotations;
	}
	
	public static function newInstance($namespaces=array(), $paths=array()) {
		$reader = new SimpleAnnotationReader();
		foreach ($namespaces as $namespace) {
			$reader->addNamespace($namespace);
		}
		$cachedReader = new CachedReader($reader, new ArrayCache());
		
		AnnotationRegistry::registerFile(__DIR__ . '/SlimAnnotations.php');
		return new RestDriver($cachedReader, $paths);
	}
	
}