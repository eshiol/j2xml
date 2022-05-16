<?php
/**
 * @package		Joomla.Plugins
 * @subpackage	System.BasicAuth
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2020 - 2021 Helios Ciancio. All Rights Reserved
 * @copyright	Copyright (C) 2011 - 2013 Johan Janssens and Timble CVBA.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * Basic HTTP authentication for Joomla is free software. This version may have
 * been modified pursuant to the GNU General Public License, and as distributed
 * it  includes or is derivative of works licensed under the GNU General Public
 * License or other free or open source software licenses.
 */
defined ('_JEXEC') or die ('Restricted access');

/**
 * Basic HTTP authentication for Joomla
 */
class plgSystemBasicauth extends JPlugin
{
	/**
	 * Application object.
	 *
	 * @var JApplicationCms
	 * @since 3.9
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param
	 *			object &$subject The object to observe
	 * @param array $config
	 *			An array that holds the plugin configuration
	 *
	 * @since 1.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$input = $this->app->input;

		// See if the client has sent authorization headers
		if (strpos(PHP_SAPI, 'cgi') !== false)
		{
			$authorization = $input->server->get('REDIRECT_HTTP_AUTHORIZATION', null, 'string');
		}
		else
		{
			$authorization = $input->server->get('HTTP_AUTHORIZATION', null, 'string');
		}

		// If basic authorization is available, store the username and password in the $_SERVER globals
		if (strstr($authorization, 'Basic'))
		{
			$parts = explode(':', base64_decode(substr($authorization, 6)));

			if (count($parts) == 2)
			{
				$input->server->set('PHP_AUTH_USER', $parts [0]);
				$input->server->set('PHP_AUTH_PW', $parts [1]);
			}
		}
	}

	/**
	 * Ask for authentication and log the user in into the application.
	 *
	 * @return void
	 * @since 1.0
	 */
	public function onAfterRoute()
	{
		$username = $this->app->input->server->get('PHP_AUTH_USER', null, 'string');
		$password = $this->app->input->server->get('PHP_AUTH_PW', null, 'string');

		if ($username && $password)
		{
			if (!$this->_login($username, $password, $this->app))
			{
				throw new Exception('Login failed', 401);
			}
		}
	}

	/**
	 * Set the HTTP Basic Authentication header
	 *
	 * @return void
	 *
	 * @since 3.9
	 */
	public function onBeforeRender()
	{
		foreach($this->app->getHeaders() as $header)
		{
			if (($header['name'] == 'status') && ($header['value'] == '401'))
			{
				$this->app->setHeader('WWW-Authenticate', 'Basic realm="' . $this->app->get( 'sitename' ) . '"');
				break;
			}
		}
	}

	/**
	 * Logs in a given user to an application.
	 *
	 * @param string $username
	 *			The username.
	 * @param string $password
	 *			The password.
	 * @param object $application
	 *			The application.
	 *
	 * @return bool True if login was successful, false otherwise.
	 * @since 1.0
	 */
	protected function _login($username, $password, $application)
	{
		// If we did receive the user credentials from the user, try to login
		if ($application->login(array('username' => $username, 'password' => $password)) !== true)
		{
			return false;
		}

		return true;
	}
}
