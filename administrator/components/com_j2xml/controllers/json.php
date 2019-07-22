<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

jimport('eshiol.j2xml.Exporter');
jimport('eshiol.j2xmlpro.Exporter');
jimport('eshiol.j2xml.Sender');

jimport('cms.response.json');

/**
 * Content controller class.
 *
 * @version 3.7.192
 * @since 3.2.135
 */
class J2XMLControllerJson extends JControllerLegacy
{

	function __construct ($default = array())
	{
		parent::__construct();
	}

	public function display ($cachable = false, $urlparams = false)
	{
		$this->input->set('view', 'content');
		parent::display($cachable, $urlparams);
	}

	function send ()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		if (! JSession::checkToken('request'))
		{
			// Check for a valid token. If invalid, send a 403 with the error
			// message.
			JLog::add(new JLogEntry(JText::_('JINVALID_TOKEN'), JLog::WARNING, 'com_j2xml'));
			echo new \JResponseJson();
			return;
		}

		$cid = (array) $this->input->get('cid', array(
				0
		), 'array');
		$sid = $this->input->get('w_id', null, 'int');

		if (! $sid)
			$sid = $this->input->get('j2xml_send_id', null, 'int');

		if (! $sid)
		{
			JLog::add(new JLogEntry(JText::_('UNKNOWN_HOST'), JLog::WARNING, 'com_j2xml'));
			echo new \JResponseJson();
			return;
		}

		$params = JComponentHelper::getParams('com_j2xml');

		$options = array();
		$options['images'] = $params->get('export_images', '1');
		$options['categories'] = 1;
		$options['users'] = $params->get('export_users', '1');
		$options['contacts'] = $params->get('export_contacts', '1');

		if (class_exists('eshiol\J2xmlpro\Exporter'))
		{
			$exporter = new eshiol\J2xmlpro\Exporter();
		}
		else
		{
			$exporter = new eshiol\J2xml\Exporter();
		}

		$get_xml = strtolower(str_replace('J2XMLController', '', get_class($this)));
		$exporter->$get_xml($cid, $xml, $options);

		$options = array();
		$options['debug'] = $params->get('debug', 0);
		$options['gzip'] = $params->get('export_gzip', '0');

		eshiol\J2xml\Sender::send($xml, $options, $sid);

		echo new \JResponseJson();
	}
}