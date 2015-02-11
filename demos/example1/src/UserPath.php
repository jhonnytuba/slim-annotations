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
					'id' => 1,
					'name' => 'Jhonatan Serafim',
					'email' => 'jhonnytuba@gmail.com',
			];
			$users[] = [
					'id' => 2,
					'name' => 'Jhonatan',
					'email' => 'jhonatanserafim@hotmail.com',
			];
			
			$this->response->setBody(json_encode($users));
		};
	}
	
	/**
	  @GET
	  @Path(":id")
	*/
	public function getUser() {
		return function($id) {
			$user = [
					'id' => $id,
					'name' => 'Jhonatan Serafim',
					'email' => 'jhonnytuba@gmail.com',
			];
			
			$this->response->setBody(json_encode($user));
		};
	}
	
}