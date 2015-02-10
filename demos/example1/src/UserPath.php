<?php
namespace Example1\UserPath;

/** @Path("users") */
class UserPath {
	
	/** @Context */
	private $app; //Is injected \Slim\Slim $app
	
	/** @Response */
	private $response; //Is injected \Slim\Slim $app->response 
	
	/** @Request */
	private $request; //Is injected \Slim\Slim $app->request
	
	/** @GET */
	public function listUsers() {
		return function() {
			$users = array();
			$users[] = [
					'codigo' => 1,
					'nome' => 'Jhonatan Serafim',
					'email' => 'jhonnytuba@gmail.com',
			];
			$users[] = [
					'codigo' => 2,
					'nome' => 'John',
					'email' => 'john@slim-annotatios.com',
			];
			
			$this->response->setBody(json_encode($users));
		};
	}
	
	/**
	  @GET
	  @Path(":codigo")
	*/
	public function getUser() {
		return function($codigo) {
			$user = [
					'codigo' => $codigo,
					'nome' => 'Jhonatan Serafim',
					'email' => 'jhonnytuba@gmail.com',
			];
			
			$this->response->setBody(json_encode($user));
		};
	}
	
}