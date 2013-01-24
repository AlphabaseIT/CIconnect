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
	
	public function deauthorize()
	{
		$this->load->library('email');
		$this->email->from('me@gerardnijboer.com', 'CIconnect');
		$this->email->to('me@gerardnijboer.com'); 
		$this->email->subject('Deauthorized');
		$this->email->message(print_r($this->CIconnect->parse_signed_request($_REQUEST['signed_request']), true));	
		$this->email->send();
		if ($this->CIconnect->logged_in() === TRUE)
		{
			$user = $this->CIconnect->delete_user($this->CIconnect->user['id']);
			if (isset($user['email']))
			{
				$this->load->library('email');
				$this->email->from('me@gerardnijboer.com', 'CIconnect');
				$this->email->to($user['email']); 
				$this->email->cc('me@gerardnijboer.com'); 
				$this->email->subject('Deauthorized');
				$this->email->message(print_r($_REQUEST, true).'We have deauthorized you from the Facebook application.');	
				$this->email->send();
			}
		}
	}
}

/* End of file connect.php */
/* Location: ./application/controllers/connect.php */