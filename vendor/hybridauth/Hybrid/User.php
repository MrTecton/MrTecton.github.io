<?php
class Hybrid_User 
{
	public $providerId = NULL;

	public $timestamp = NULL; 

	public $profile = NULL;

	function __construct()
	{
		$this->timestamp = time(); 

		$this->profile   = new Hybrid_User_Profile(); 
	}
}
