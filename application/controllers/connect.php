<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Connect extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		
		// Load Facebook library
		$Facebook['appId'] = 'YOUR_APP_ID_HERE';
		$Facebook['secret'] = 'YOUR_APP_SECRET_HERE';
		$this->load->library('facebook', $Facebook, 'FacebookApplication');
		
		// Integrate Facebook library
		$this->load->model('CIC');
		$this->CIC->initiate($this->FacebookApplication);
		// Determine which scope to require for access to this application
		// Leave blank for standard scope
		// More: http://developers.facebook.com/docs/reference/api/permissions/
		$this->CIC->setScope('email,manage_pages', true);
		
	}

	public function index() {
	?><a href="<?=$this->CIC->getLoginUrl();?>">Login</a><?
	if ($this->CIC->loggedIn() == true) {
		var_dump($this->CIC->User);
	}
	}
}