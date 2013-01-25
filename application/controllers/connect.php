<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Connect extends CI_Controller
{
	
	public function __construct()
	{
		parent::__construct();
		
		ini_set('display_errors', 1);
		
		// Load Facebook config and library
		$this->config->load('ciconnect');
		$this->load->library('facebook', $this->config->item('app_test'), 'FacebookApplication');
		
		// Integrate Facebook library
		$this->load->model('CIconnect');
		$this->CIconnect->initiate($this->FacebookApplication);
		// Determine which scope to require for access to this application
		// Leave blank for standard scope
		// More: http://developers.facebook.com/docs/reference/api/permissions/
		$this->CIconnect->set_scope('email,manage_pages', TRUE);
		$this->CIconnect->set_redirect_url(base_url('connect'));
	}

	public function index()
	{
		?><a href="<?=base_url('connect/login');?>">Login</a><?
		if ($this->CIconnect->logged_in() == TRUE)
		{
			var_dump($this->CIconnect->user);
		}
	}
	
	public function login()
	{
		if ($this->CIconnect->logged_in() == FALSE)
		{
			redirect($this->CIconnect->get_login_url());
		}
		else
		{
			redirect(base_url());
		}
	}
	
	public function logout()
	{
		if ($this->CIconnect->logged_in() == TRUE)
		{
			redirect($this->CIconnect->get_logout_url());
		}
		else
		{
			redirect(base_url());
		}
	}
	
	public function deauthorize()
	{
		$config = $this->config->item('app_test');
		$request = $this->CIconnect->parse_signed_request($_REQUEST['signed_request']);
		$user = $this->CIconnect->delete_user($request->user_id);
		
		$this->load->library('email');
		$this->email->from($config['admin'], $config['name']);
		$this->email->to($config['admin']); 
		$this->email->subject('Deauthorized from '.$config['name'].': '.$user['name']);
		$this->email->message(print_r($request, TRUE)."\n\n".print_r($user, TRUE));	
		$this->email->send();
		if (isset($user['email']))
		{
			$this->load->library('email');
			$this->email->from($config['admin'], $config['name']);
			$this->email->to($user['email']); 
			$this->email->cc($config['admin']); 
			$this->email->subject('Deauthorized from '.$config['name']);
			$this->email->message('We have successfully deauthorized you from the Facebook application '.$config['name'].'.');	
			$this->email->send();
		}
	}
}

/* End of file connect.php */
/* Location: ./application/controllers/connect.php */