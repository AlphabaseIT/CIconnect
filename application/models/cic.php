<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CIC extends CI_Model {
	
	// Set global variables
	public $User;
	public $Link;
	private $Scope;
	private $Redirect;
	
	// Called every time the model is used
	public function __construct() {
		parent::__construct();
		$this->Scope = NULL;
	}
	
	// Manual initiation of model, required!
	// Requiring the link to the Facebook library/application
	public function initiate($Link) {
		$this->Link = $Link;
		$this->getUser();
		$this->checkForUpdates();
	}
	
	// Change scope to change application permissions
	public function setScope($Scope, $Force = false) {
		$this->Scope = $Scope;
		if ($Force == true) {
			$this->hasPermissions($Scope, true);
		}
	}
	
	// Set the redirect URL for after authorization
	public function setRedirect($Url) {
		$this->Redirect = $Url;
	}
	
	// Get information about the current user
	public function getUser() {
		$this->User = $this->Link->getUser();
		if ($this->User) {
			// Try to fetch something if user is logged in
			try {
				$this->User = $this->Link->api('/me');
			} catch (FacebookApiException $e) {
				error_log($e);
				$this->User = null;
			}
		}
		return $this->User;
	}
	
	// Execute a query to the Facebook library/application through the Graph API
	// More: http://developers.facebook.com/docs/reference/api/
	public function getData($Query) {
		return $this->Link->api($Query);
	}
	
	// Get login URL, including any scope and redirect URL if set
	public function getLoginUrl() {
		$Params = array();
		if (isset($this->Scope)) $Params['scope'] = $this->Scope;
		if (isset($this->Redirect)) $Params['redirect_uri'] = $this->Redirect;
		return $this->Link->getLoginUrl($Params);
	}
	
	// Get logout URL
	public function getLogoutUrl() {
		return $this->Link->getLogoutUrl();
	}
	
	// Return boolean if user is logged in
	public function loggedIn() {
		if (isset($this->User['id'])) {
			return true;
		}
		return false;
	}
	
	// Check of a set of permissions is set
	public function hasPermissions($Permissions, $Force=false) {
		if ($this->loggedIn() == true) {
			$Current = $this->getData('me/permissions');
			$Permissions = explode(',', $Permissions);
			foreach($Permissions as $Permission) {
				if (array_key_exists($Permission, $Current['data'][0]) == false) {
					if ($Force == true) {
						exit('<script> top.location.href="' . $this->getLoginUrl() . '"</script>');
					}
					return false;
				}
			}
		}
		return true;
	}
	
	// Check of the user is admin of a given page
	public function isPageAdmin($Page) {
		return $this->Link->api(array('method' => 'pages.isadmin', 'page_id' => $Page));
	}
	
	// Check if we already have this user in our database
	public function isNewUser($ID) {
		if ($this->db->query('SELECT id FROM cic_users WHERE id=\''.$ID.'\';')->num_rows() == 0) {
			return true;
		}
		return false;
	}
	
	// Check if the local user account in database requires an update
	public function accountNeedsUpdate($ID, $Timestamp) {
		$Row = $this->db->query('SELECT updated_time FROM cic_users WHERE id=\''.$ID.'\';')->row();
		if ($Row && $Row->updated_time != $Timestamp) {
			return true;
		}
		return false;
	}
	
	// Update account, either existing or new in database
	public function updateAccount($User, $New = false) {
		$User = array(
			'id' => $User['id'],
			'name' => (isset($User['name']) ? $User['name'] : NULL),
			'gender' => (isset($User['gender']) ? $User['gender'] : NULL),
			'email' => (isset($User['email']) ? $User['email'] : NULL),
			'locale' => (isset($User['locale']) ? $User['locale'] : NULL),
			'link' => (isset($User['link']) ? $User['link'] : NULL),
			'updated_time' => $User['updated_time'],
			'profile' => json_encode($User)
		);
		if ($New == true) {
			$this->load->helper('date');
			$User['joined'] = standard_date('DATE_W3C', time());
			$this->db->insert('cic_users', $User);
		} elseif ($New == false) {
			$this->db->where('id', $User['id']);
			$this->db->update('cic_users', $User); 
		}
	}
	
	// Check if the local database needs update
	public function checkForUpdates() {
		if ($this->loggedIn()) {
			if ($this->isNewUser($this->User['id'])) {
				$this->updateAccount($this->User, true);
			} elseif ($this->accountNeedsUpdate($this->User['id'], $this->User['updated_time'])) {
				$this->updateAccount($this->User);
			}
		}
	}
	
	public function postToWall($Params) {
		return $this->Link->api('/me/feed', 'post', $Params);
		// message
		// link
		// name
		// description
		// picture
		// access_token
	}
	
	
	public function deleteUser($ID) {
		$User = $this->db->query('
			SELECT
				*
			FROM
				cic_users
			WHERE
				id = '.$this->db->escape($ID).'
		;')->row_array();
		$this->db->query('
			DELETE FROM
				cic_users
			WHERE
				id = '.$this->db->escape($ID).'
			;');
		return $User;
	}
		
}
?>