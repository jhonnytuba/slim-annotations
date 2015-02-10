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
					'codigo' => 1,
					'nome' => 'One',
			];
			$books[] = [
					'codigo' => 2,
					'nome' => 'Two',
			];
			
			$this->response->setBody(json_encode($books));
		};
	}
	
}