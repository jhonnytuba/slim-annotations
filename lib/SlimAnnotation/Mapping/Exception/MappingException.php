<?php
namespace SlimAnnotation\Mapping\Exception;

class MappingException extends \Exception {
	
	public static function pathRequired() {
		return new self("Specifying the paths to your entities is required " . "in the AnnotationDriver to retrieve all class names.");
	}
	
	public static function fileMappingDriversRequireConfiguredDirectoryPath($path = null) {
		if (!empty($path)) {
			$path = '[' . $path . ']';
		}
		
		return new self('File mapping drivers must have a valid directory path, ' . 'however the given path ' . $path . ' seems to be incorrect!');
	}
	
}