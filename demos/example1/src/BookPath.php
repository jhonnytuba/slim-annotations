<?php
namespace Example1\BookPath;

/** @Path */
class BookPath {
	
	/** @Response */
	private $response; //Is injected \Slim\Slim $app->response 
	
	/** 
	  @GET
	  @Path("books")
	*/
	public function listBook() {
		return function() {
			$books = array();
			$books[] = [
					'id' => 1,
					'name' => 'One',
			];
			$books[] = [
					'id' => 2,
					'name' => 'Two',
			];
			
			$this->response->setBody(json_encode($books));
		};
	}
	
}