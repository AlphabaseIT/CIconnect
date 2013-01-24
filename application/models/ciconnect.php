<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CI connect
 *
 * @author	Alphabase IT
 * @link	http://alphabase.it
 */

class CIconnect extends CI_Model
{

	/**
	 * Set class variables
	 */
	public $user;
	public $link;
	private $_scope;
	private $_redirect;

	/**
	 * Called every time the model is used
	 *
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_scope = NULL;
	}

	/**
	 * Manual initiation of model, required!
	 * Requiring the link to the Facebook library/application
	 *
	 * @access	public
	 * @param	facebooklink	$link	An initiation of the Facebook PHP library
	 */
	public function initiate($link)
	{
		$this->link = $link;
		$this->get_user();
		$this->check_for_updates();
	}

	/**
	 * Change scope to change application permissions
	 * 
	 * @access	public
	 * @param	string	$scope	Comma-separated list of scope entities
	 * @param	bool	$force	Whether or not to force the presence of permissions
	 */
	public function set_scope($scope, $force = FALSE)
	{
		$this->_scope = $scope;
		if ($force == TRUE)
		{
			$this->has_permissions($scope, TRUE);
		}
	}

	/**
	 * Set the redirect URL that follows after authorization
	 *
	 * @access	public
	 * @param	string	$url	The full url to redirect to
	 */
	public function set_redirect_url($url)
	{
		$this->_redirect = $url;
	}

	/**
	 * Get information about the current user
	 *
	 * @access	public
	 * @return	object	The user object from Facebook
	 */
	public function get_user()
	{
		$this->user = $this->link->getUser();
		if ($this->user)
		{
			// Try to fetch something if user is logged in
			try
			{
				$this->user = $this->link->api('/me');
				// If anything fails, set the current user as for being NULL
			}
			catch (FacebookApiException $e)
			{
				error_log($e);
				$this->user = NULL;
			}
		}
		return $this->user;
	}

	/**
	 * Execute a query to the Facebook library/application through the Graph API
	 * More: http://developers.facebook.com/docs/reference/api/
	 * 
	 * @access	public
	 * @param	string	$query	The query to perform with the graph api
	 * @return	object
	 */
	public function api($query)
	{
		return $this->link->api($query);
	}

	/**
	 * Get login URL, including any scope and redirect URL if set
	 * 
	 * @access	public
	 */
	public function get_login_url()
	{
		$params = array();
		if (isset($this->_scope)) $params['scope'] = $this->_scope;
		if (isset($this->_redirect)) $params['redirect_uri'] = $this->_redirect;
		return $this->link->getLoginUrl($params);
	}

	/**
	 * Get logout URL
	 * 
	 * @access	public
	 */
	public function get_logout_url()
	{
		return $this->link->getLogoutUrl();
	}

	/**
	 * Return boolean if user is logged in
	 * 
	 * @access	public
	 */
	public function logged_in()
	{
		if (isset($this->user['id']))
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check if a set of permissions is set
	 * 
	 * @access	public
	 * @param	string	$permissions	Comma-separated list of scope entities
	 * @param	bool	$force	Whether or not to force the having of these permissions
	 */
	public function has_permissions($permissions, $force = FALSE)
	{
		if ($this->logged_in() == TRUE)
		{
			$current = $this->api('me/permissions');
			$permissions = explode(',', $permissions);
			foreach($permissions as $permission)
			{
				if (array_key_exists($permission, $current['data'][0]) == FALSE)
				{
					if ($force == TRUE)
					{
						exit('<script> top.location.href="' . $this->get_login_url() . '"</script>');
					}
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	/**
	 * Check of the user is admin of a given page
	 * 
	 * @access	public
	 * @param	int	$page	The page id
	 */
	public function is_page_admin($page)
	{
		return $this->link->api(array('method' => 'pages.isadmin', 'page_id' => $page));
	}
	
	/**
	 * Check if we already have this user in our database
	 * 
	 * @access	public
	 * @param	int	$id	The user id
	 */
	public function is_new_user($id)
	{
		if ($this->db->query('SELECT id FROM cic_users WHERE id=\''.$id.'\';')->num_rows() == 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check if the local user account in database requires an update
	 * 
	 * @access	public
	 * @param	int	$id	The user id
	 * @param	string	$timestamp	The timestamp for last updating the user profile as delivered by Facebook
	 */
	public function account_needs_update($id, $timestamp)
	{
		$row = $this->db->query('SELECT updated_time FROM cic_users WHERE id=\''.$id.'\';')->row();
		if ($row && $row->updated_time != $timestamp)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Update account, either existing or new in database
	 * 
	 * @access	public
	 * @param	array	$user	The user object
	 * @param	bool	$new	Whether we are inserting a new profile or updating an existing one
	 */
	public function update_account($user, $new = FALSE)
	{
		$user = array(
			'id' => $user['id'],
			'name' => (isset($user['name']) ? $user['name'] : NULL),
			'gender' => (isset($user['gender']) ? $user['gender'] : NULL),
			'email' => (isset($user['email']) ? $user['email'] : NULL),
			'locale' => (isset($user['locale']) ? $user['locale'] : NULL),
			'link' => (isset($user['link']) ? $user['link'] : NULL),
			'updated_time' => $user['updated_time'],
			'profile' => json_encode($user)
		);
		if ($new == TRUE)
		{
			$this->load->helper('date');
			$user['joined'] = standard_date('DATE_W3C', time());
			$this->db->insert('cic_users', $user);
		}
		elseif ($new == FALSE)
		{
			$this->db->where('id', $user['id']);
			$this->db->update('cic_users', $user); 
		}
	}

	/**
	 * Check if the local database needs update
	 * 
	 * @access	public
	 */
	public function check_for_updates()
	{
		if ($this->logged_in())
		{
			if ($this->is_new_user($this->user['id']))
			{
				$this->update_account($this->user, TRUE);
			}
			elseif ($this->account_needs_update($this->user['id'], $this->user['updated_time']))
			{
				$this->update_account($this->user);
			}
		}
	}

	/**
	 * Post a message to the user's wall
	 * 
	 * @access	public
	 * @param	array	$params	Parameters to pass to posting to the user wall
	 */
	public function post_to_wall($params)
	{
		return $this->link->api('/me/feed', 'post', $params);
		// message
		// link
		// name
		// description
		// picture
		// access_token
	}

	/**
	 * Delete the user details from the database when de-authorized
	 * 
	 * @access	public
	 * @param	int	$id	The user id
	 */
	public function delete_user($id)
	{
		$user = $this->db->query('
			SELECT
				*
			FROM
				cic_users
			WHERE
				id = '.$this->db->escape($id).'
		;')->row_array();
		$this->db->query('
			DELETE FROM
				cic_users
			WHERE
				id = '.$this->db->escape($id).'
			;');
		return $user;
	}
	
	/**
	 * Parse a signed request
	 * 
	 * @access	public
	 * @param	string	$request	The signed request delivered by Facebook
	 */
	public function parse_signed_request($request = FALSE)
	{
		if ($request != FALSE)
		{
			$encoded_sig = NULL;
			$payload = NULL;
			list($encoded_sig, $payload) = explode('.', $request, 2);
			$sig = base64_decode(strtr($encoded_sig, '-_', '+/'));
			$data = json_decode(base64_decode(strtr($payload, '-_', '+/'), TRUE));
			return $data;
		}
		return FALSE;
	}

}

/* End of file ciconnect.php */
/* Location: ./application/models/ciconnect.php */