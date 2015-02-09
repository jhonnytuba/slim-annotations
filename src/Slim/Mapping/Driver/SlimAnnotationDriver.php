<?php
namespace SlimAnnotation\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Cache\ArrayCache;

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
	
	public function loadAnnotationsForApp(\Slim\Slim $app) {
		foreach ($this->getAllClassNames() as $className) {
			$class = new \ReflectionClass($className);
			
			$classAnnotations = $this->getClassAnnotations($class);
			
			if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Middleware'])) {
				$app->add($class->newInstance());
			}
			else if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Middleware'])) {
				$app->add($class->newInstance());
			}
			else if (isset($classAnnotations['SlimAnnotation\Mapping\Annotation\Path'])) {
				$restAnnotation = $classAnnotations['SlimAnnotation\Mapping\Annotation\Path'];
				
				foreach ($class->getMethods() as $method) {
					$instanceClass = $class->newInstance($app);
					$methodAnnotations = getMethodAnnotations($method);
				
					if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\POST'])) {
						$app->post($restAnnotation->uri, $method->invoke($instanceClass));
					}
					else if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\GET'])) {
						$app->get($restAnnotation->uri, $method->invoke($instanceClass));
					}
					else if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\DELETE'])) {
						$app->delete($restAnnotation->uri, $method->invoke($instanceClass));
					}
					else if (isset($methodAnnotations['SlimAnnotation\Mapping\Annotation\PUT'])) {
						$app->put($restAnnotation->uri, $method->invoke($instanceClass));
					}
				}
			}
		}
	}
	
	private function getClassAnnotations(\ReflectionClass $class) {
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
	
	private function getMethodAnnotations(\ReflectionMethod $method) {
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