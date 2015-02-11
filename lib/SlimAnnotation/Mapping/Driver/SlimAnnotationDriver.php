<?php
namespace SlimAnnotation\Mapping\Driver;

use \Doctrine\Common\Annotations\SimpleAnnotationReader;
use \Doctrine\Common\Annotations\CachedReader;
use \Doctrine\Common\Annotations\AnnotationRegistry;
use \Doctrine\Common\Cache\ArrayCache;
use \Slim\Slim;
use \SlimAnnotation\Mapping\Annotation\ApplicationPath;
use \SlimAnnotation\Mapping\Annotation\Path;
use \SlimAnnotation\Mapping\Exception\MappingException;

class SlimAnnotationDriver {
	
	protected $reader;
	protected $paths;
	protected $excludePaths;
	protected $fileExtension;
	protected $classNames;
	protected $entityAnnotationClasses;
	
	public function __construct($reader, $paths=array(), $excludePaths=array()) {
		$this->reader = $reader;
		$this->paths = $paths;
		$this->excludePaths = $excludePaths;
		
		$this->fileExtension = '.php';
		$this->entityAnnotationClasses = array(
				'SlimAnnotation\Mapping\Annotation\ApplicationPath' => 1,
				'SlimAnnotation\Mapping\Annotation\Path' => 2,
				'SlimAnnotation\Mapping\Annotation\Middleware' => 3,
		);
	}
	
	public function createAppWithAnnotations() {
		$app = new Slim();
		
		foreach ($this->getAllClassNames() as $className) {
			$class = new \ReflectionClass($className);
			$this->loadClassAnnotations($app, $class);
		}
		
		return $app;
	}
	
	private function loadClassAnnotations(Slim $app, \ReflectionClass $class) {
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
	
	private function loadPath(Slim $app, \ReflectionClass $class, $newInstanceClass, Path $pathAnnotation) {
		foreach ($class->getMethods() as $method) {
			$this->loadMethodAnnotations($app, $method, $newInstanceClass, $pathAnnotation->uri);
		}
	}
	
	private function loadMethodAnnotations(Slim $app, \ReflectionMethod $method, $newInstanceClass, $uri) {
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
	
	private function loadAttributesAnnotations(Slim $app, \ReflectionClass $class, $newInstanceClass) {
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
	
	private function getPropertyAnnotations(\ReflectionProperty $property) {
		$propertyAnnotations = $this->reader->getPropertyAnnotations($property);
		foreach ($propertyAnnotations as $key => $annot) {
			if ( ! is_numeric($key)) {
				continue;
			}
			$propertyAnnotations[get_class($annot)] = $annot;
		}
		return $propertyAnnotations;
	}
	
	private function isTransient($className) {
		$classAnnotations = $this->reader->getClassAnnotations(new \ReflectionClass($className));
	
		foreach ($classAnnotations as $annot) {
			if (isset($this->entityAnnotationClasses[get_class($annot)])) {
				return false;
			}
		}
		return true;
	}

    private function getAllClassNames() {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        if (!$this->paths) {
            throw MappingException::pathRequired();
        }

        $classes = array();
        $includedFiles = array();

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+' . preg_quote($this->fileExtension) . '$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                foreach ($this->excludePaths as $excludePath) {
                    $exclude = str_replace('\\', '/', realpath($excludePath));
                    $current = str_replace('\\', '/', $sourceFile);

                    if (strpos($current, $exclude) !== false) {
                        continue 2;
                    }
                }

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) && !$this->isTransient($className)) {
                $classes[] = $className;
            }
        }

        $this->classNames = $classes;

        return $classes;
    }
	
	public static function newInstance($paths=array(), $excludePaths=array()) {
		$reader = new SimpleAnnotationReader();
		$reader->addNamespace('SlimAnnotation\Mapping\Annotation');
		$cachedReader = new CachedReader($reader, new ArrayCache());
		
		AnnotationRegistry::registerFile(__DIR__ . '/SlimAnnotations.php');
		return new SlimAnnotationDriver($cachedReader, $paths, $excludePaths);
	}
	
}