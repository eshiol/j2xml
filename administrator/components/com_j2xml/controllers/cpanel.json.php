<?php
/**
 * @version		3.6.167 administrator/components/com_j2xml/controllers/cpanel.json.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.6.160
 * 
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2017 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('joomla.application.component.controller');

jimport('eshiol.j2xml.importer');
jimport('eshiol.j2xml.version');

jimport('cms.response.json');

require_once JPATH_ADMINISTRATOR.'/components/com_j2xml/helpers/j2xml.php';

/**
 * Content controller class.
 */
class J2XMLControllerCpanel extends JControllerLegacy
{
	/**
	 * The application object.
	 *
	 * @var    JApplicationBase
	 * @since  3.6.160
	 */
	protected $app;

	function __construct($default = array())
	{
		parent::__construct();
		$this->app = JFactory::getApplication();
	}

	function import()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$data = utf8_encode(urldecode($this->app->input->post->get('j2xml_data', '', 'RAW')));
		$filename = utf8_encode(urldecode($this->app->input->post->get('j2xml_filename', '', 'RAW')));

		// Send json mime type.
		$this->app->mimeType = 'application/json';
		$this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
		$this->app->sendHeaders();

		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');

		$params = JComponentHelper::getParams('com_j2xml');

		$results = $dispatcher->trigger('onContentPrepareData', array('com_j2xml.cpanel', &$data));
		$data = strstr($data, '<?xml version="1.0" ');

		$data = J2XMLHelper::stripInvalidXml($data);
		if (!defined('LIBXML_PARSEHUGE'))
		{
			define(LIBXML_PARSEHUGE, 524288);
		}
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);

		JLog::add(new JLogEntry('data: '.$data, JLog::DEBUG, 'com_j2xml'));
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);

		if (!$xml)
		{
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$msg = $error->code.' - '.$error->message.' at line '.$error->line;
				switch ($error->level) {
					default:
					case LIBXML_ERR_WARNING:
						$this->app->enqueueMessage($msg,'message');
						break;
					case LIBXML_ERR_ERROR:
						$this->app->enqueueMessage($msg,'notice');
						break;
					case LIBXML_ERR_FATAL:
						$this->app->enqueueMessage($msg,'error');
						break;
				}
			}
			libxml_clear_errors();
		}

		if (!$xml)
		{
			echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}

		$results = $dispatcher->trigger('onBeforeImport', array('com_j2xml.cpanel', &$xml));

		$results = $dispatcher->trigger('onBeforeImport', array('com_j2xml.cpanel', &$xml));

		if (!$xml)
		{
			echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}
		elseif (strtoupper($xml->getName()) == 'J2XML')
		{
			if(!isset($xml['version']))
			{
				$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
			}
			else
			{
				jimport('eshiol.j2xml.importer');

				$params->set('filename', $filename);

				//set_time_limit(120);
				$j2xml = new J2XMLImporter();
				$j2xml->import($xml, $params);
			}
		}

		if (!$xml)
		{
			echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}
		else
		{
			jimport('eshiol.j2xml.importer');

			$params->set('filename', $filename);

			//set_time_limit(120);
			$j2xml = new J2XMLImporter();
			$j2xml->import($xml, $params);
		}

		JLog::add(new JLogEntry(print_r($this->app->getMessageQueue(), true), JLog::DEBUG, 'com_j2xml'));

		echo new JResponseJson($response = null, $message = $this->app->getMessageQueue(), $error = false, $ignoreMessages = false);
		$this->app->close();
	}
}