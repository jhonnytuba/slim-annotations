<?php
namespace SlimAnnotation\Mapping\Driver;

use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionProperty;
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
	
	public function __construct($reader, $paths = null) {
		parent::__construct($reader, $paths);
	}
	
	public function loadMetadataForClass($className, ClassMetadata $metadata) {
	}
	
	public function createAppWithAnnotations() {
		$app = new Slim();
		
		foreach ($this->getAllClassNames() as $className) {
			$class = new ReflectionClass($className);
			$this->loadClassAnnotations($app, $class);
		}
		
		return $app;
	}
	
	private function loadClassAnnotations(Slim $app, ReflectionClass $class) {
		$classAnnotations = $this->getClassAnnotations($class);
		$newInstanceClass = $class->newInstance();
			
		if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\ApplicationPath'])) {
			$this->loadApplicationPath($app, $classAnnotations['SlimAnnotation\Mapping\Annotation\ApplicationPath']);
			
			$this->loadAttributesAnnotations($app, $class, $newInstanceClass);
		}
		
		if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Middleware'])) {
			$this->loadMiddleware($app, $newInstanceClass);
		}
		
		if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Path'])) {
			$this->loadPath($app, $class, $newInstanceClass, $classAnnotations['SlimAnnotation\Mapping\Annotation\Path']);
			
			$this->loadAttributesAnnotations($app, $class, $newInstanceClass);
		}
	}
	
	private function loadApplicationPath(Slim $app, ApplicationPath $classAnnotation) {
		if ($classAnnotation->responseContentType) {
			$app->response()->header('content-type', $classAnnotation->responseContentType);
		}
	}
	
	private function loadMiddleware(Slim $app, $newInstanceClass) {
		$app->add($newInstanceClass);
	}
	
	private function loadPath(Slim $app, ReflectionClass $class, $newInstanceClass, Path $pathAnnotation) {
		foreach ($class->getMethods() as $method) {
			$this->loadMethodAnnotations($app, $method, $newInstanceClass, $pathAnnotation->uri);
		}
	}
	
	private function loadMethodAnnotations(Slim $app, ReflectionMethod $method, $newInstanceClass, $uri) {
		$methodAnnotations = $this->getMethodAnnotations($method);
		
		$uriMethod = '';
		if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\Path'])) {
			$uriMethod = $methodAnnotations['SlimAnnotation\Mapping\Annotation\Path']->uri;
		}
		
		$uri = $this->normalizeURI($uri, $uriMethod);
		
		if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\POST'])) {
			$app->post($uri, $method->invoke($newInstanceClass));
		}
		
		if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\GET'])) {
			$app->get($uri, $method->invoke($newInstanceClass));
		}
		
		if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\DELETE'])) {
			$app->delete($uri, $method->invoke($newInstanceClass));
		}
		
		if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\PUT'])) {
			$app->put($uri, $method->invoke($newInstanceClass));
		}
	}
	
	private function loadAttributesAnnotations(Slim $app, ReflectionClass $class, $newInstanceClass) {
		foreach ($class->getProperties() as $property) {
			$propertyAnnotations = $this->getPropertyAnnotations($property);
			$inject = null;
			
			if (isset($propertyAnnotations['SlimAnnotation\Mapping\Annotation\Context'])) {
				$inject = $app;
			}
			else if (isset($propertyAnnotations['SlimAnnotation\Mapping\Annotation\Request'])) {
				$inject = $app->request;
			}
			else if (isset($propertyAnnotations['SlimAnnotation\Mapping\Annotation\Response'])) {
				$inject = $app->response;
			}
			
			$property->setAccessible(true);
			$property->setValue($newInstanceClass, $inject);
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
	
	private function getPropertyAnnotations(ReflectionProperty $property) {
		$propertyAnnotations = $this->reader->getPropertyAnnotations($property);
		foreach ($propertyAnnotations as $key => $annot) {
			if ( ! is_numeric($key)) {
				continue;
			}
			$propertyAnnotations[get_class($annot)] = $annot;
		}
		return $propertyAnnotations;
	}
	
	public static function newInstance($paths=array()) {
		$reader = new SimpleAnnotationReader();
		$reader->addNamespace('SlimAnnotation\Mapping\Annotation');
		$cachedReader = new CachedReader($reader, new ArrayCache());
		
		AnnotationRegistry::registerFile(__DIR__ . '/SlimAnnotations.php');
		return new SlimAnnotationDriver($cachedReader, $paths);
	}
	
}