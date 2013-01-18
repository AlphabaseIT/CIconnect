<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Connect extends CI_Controller
{
	
	public function __construct()
	{
		parent::__construct();
		
		ini_set('display_errors', 1);
		
		// Load Facebook library
		$facebook['appId'] = '';
		$facebook['secret'] = '';
		$this->load->library('facebook', $facebook, 'FacebookApplication');
		
		// Integrate Facebook library
		$this->load->model('CIconnect');
		$this->CIconnect->initiate($this->FacebookApplication);
		// Determine which scope to require for access to this application
		// Leave blank for standard scope
		// More: http://developers.facebook.com/docs/reference/api/permissions/
		$this->CIconnect->set_scope('email,manage_pages', TRUE);
	}

	public function index()
	{
		?><a href="<?=$this->CIconnect->get_login_url();?>">Login</a><?
		if ($this->CIconnect->logged_in() == true)
		{
			var_dump($this->CIconnect->user);
		}
	}
}