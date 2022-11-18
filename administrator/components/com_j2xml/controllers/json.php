<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controller');

use eshiol\J2xml\Exporter;
use eshiol\J2xml\Sender;
use eshiol\J2xml\Version;

JLoader::import('eshiol.J2xml.Exporter');
JLoader::import('eshiol.J2xml.Sender');
JLoader::import('eshiol.J2xml.Version');


/**
 * Content controller class.
 */
class J2xmlControllerJson extends JControllerLegacy
{

	/**
	 * The params object
	 *
	 * @var JRegistry
	 */
	protected $params;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 *			An optional associative array of configuration settings.
	 *			Recognized key values include 'name', 'default_task', 'model_path', and
	 *			'view_path' (this list is not meant to be comprehensive).
	 *
	 * @since 12.2
	 */
	public function __construct($config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		parent::__construct($config);

		$this->params = new JRegistry();
	}

	/**
	 * Export content
	 */
	function export()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		if (! JSession::checkToken('request')) {
			// Check for a valid token. If invalid, send a 403 with the error message.
			JLog::add(new JLogEntry(JText::_('JINVALID_TOKEN'), JLog::WARNING, 'com_j2xml'));
			echo new JResponseJson();
			return;
		}

		$app    = JFactory::getApplication();
		$data   = $app->input->post->getArray();
		// Save the posted data in the session.
		$app->setUserState('com_j2xml.send.data', $data);
		JLog::add(new JLogEntry('setUserState(\'com_j2xml.send.data\'): ' . print_r($data, true), JLog::DEBUG, 'com_j2xml'));

		$cid    = (array) $this->input->get('cid', array(0), 'array');

		$j2xml  = new Exporter();
		$export = strtolower(str_replace('J2xmlController', '', get_class($this)));
		$j2xml->$export($cid, $xml, $this->params);

		$app = JFactory::getApplication();
		$version = explode(".", Version::$DOCVERSION);
		$xmlVersionNumber = $version[0] . $version[1] . substr('0' . $version[2], strlen($version[2]) - 1);

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();

		echo new JResponseJson($data);
	}
}
