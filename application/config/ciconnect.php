<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* This is the global configuration for the CIconnect model
* Load the configuration into the controller to initiate the Facebook PHP SDK library with

** AUTOMATIC LOADING
* To automatically load the configuration, edit the following file:
* /application/config/autoload.php
*
* Then find at line 83 the autoloading of configuration files, and add 'ciconnect' to the array.
* Example: $autoload['config'] = array('some_file', 'other_file', 'ciconnect');

** MANUAL LOADING
* To manually load the configuration, add the following code to your controller:
* $this->config->load('ciconnect');

** TO ADD MULTIPLE APPLICATIONS
* It could be that your CodeIgniter hosts multiple applications.
* The extension is designd to support multiple Facebook applications through the config-file.
* Simply duplicate the example config array in this file and give it an identifier with the $config-array key.

** NOTATION
* The configuration file expects the following notation for the configuration arrays.
* Change any variable in uppercase with the details of your Facebook-application.

$config['APP_IDENTIFIER'] = array(
	'name'		=> 'APP_NAME',
	'appId'		=> 'APP_ID',
	'secret'	=> 'APP_SECRET',
	'admin'		=> 'ADMIN_EMAIL',
	'debug'		=> TRUE/FALSE
);

**/

$config['APP_IDENTIFIER'] = array(
	'name'		=> 'APP_NAME',
	'appId'		=> 'APP_ID',
	'secret'	=> 'APP_SECRET',
	'admin'		=> 'ADMIN_EMAIL',
	'debug'		=> TRUE/FALSE
);

/* End of file ciconnect.php */
/* Location: ./application/config/ciconnect.php */