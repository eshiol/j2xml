<?php
/**
 * @version		3.6.162 administrator/components/com_j2xml/controllers/cpanel.json.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.6.160
 * 
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2016 Helios Ciancio. All Rights Reserved
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
		JLog::add(new JLogEntry(__LINE__.' '.__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$data = utf8_encode(urldecode($this->app->input->post->get('j2xml_data', '', 'RAW')));

		// Send json mime type.
		$this->app->mimeType = 'application/json';
		$this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
		$this->app->sendHeaders();
		
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');

		$results = $dispatcher->trigger('onContentPrepareData', array('com_j2xml.cpanel', &$data));
		$data = strstr($data, '<?xml version="1.0" ');
		
		$data = J2XMLHelper::stripInvalidXml($data);
		if (!defined('LIBXML_PARSEHUGE'))
		{
			define(LIBXML_PARSEHUGE, 524288);
		}
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);
JLog::add(new JLogEntry(__LINE__, JLog::DEBUG, 'com_j2xml'));
		
		JLog::add(new JLogEntry('data: '.$data, JLog::DEBUG, 'com_j2xml'));
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		
JLog::add(new JLogEntry(__LINE__, JLog::DEBUG, 'com_j2xml'));
		if (!$xml)
		{
JLog::add(new JLogEntry(__LINE__, JLog::DEBUG, 'com_j2xml'));
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
		
		if (!$xml)
		{
			echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}
		
		$results = $dispatcher->trigger('onBeforeImport', array('com_j2xml.cpanel', &$xml));
		
		if (!$xml)
		{
			echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}
		
		if (strtoupper($xml->getName()) == 'J2XML')
		{
			if(!isset($xml['version']))
			{
				echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
				$this->app->close();
				return false;
			}

			$xmlVersion = $xml['version'];
			$version = explode(".", $xmlVersion);
			$xmlVersionNumber = $version[0].substr('0'.$version[1], strlen($version[1])-1).substr('0'.$version[2], strlen($version[2])-1);

			$j2xmlVersion = J2XMLVersion::$DOCVERSION;
			$version = explode(".", $j2xmlVersion);
			$j2xmlVersionNumber = $version[0].substr('0'.$version[1], strlen($version[1])-1).substr('0'.$version[2], strlen($version[2])-1);

			if (($xmlVersionNumber == $j2xmlVersionNumber) || ($xmlVersionNumber == "120500"))
			{
				//set_time_limit(120);
				$params = JComponentHelper::getParams('com_j2xml');
				$j2xml = new J2XMLImporter();
				$j2xml->import($xml,$params);
			}
			elseif ($xmlVersionNumber == 10506)
			{
				echo new JResponseJson($response = null, $message = JText::sprintf('COM_J2XML_MSG_FILE_FORMAT_J2XML15', $xmlVersion), $error = true, $ignoreMessages = false);
				$this->app->close();
				return false;
			}
			else
			{
				echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion), $error = true, $ignoreMessages = false);
				$this->app->close();
				return false;
			}
		}
		elseif (strtoupper($xml->getName()) == 'RSS')
		{
			$namespaces = $xml->getNamespaces(true);
			if (isset($namespaces['wp']))
			{
				if ($generator = $xml->xpath('/rss/channel/generator'))
				{
					if (preg_match("/http:\/\/wordpress.(org|com)\//", (string)$generator[0]) != false)
					{
						$xml->registerXPathNamespace('wp', $namespaces['wp']);
						if (!($wp_version = $xml->xpath('/rss/channel/wp:wxr_version')))
						{
							echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
							$this->app->close();
							return false;
						}
						else if ($wp_version[0] == '1.2')
						{
								echo new JResponseJson($response = null, $message = JText::_('COM_J2XML_MSG_FILE_FORMAT_J2XMLWP'), $error = true, $ignoreMessages = false);
								$this->app->close();
								return false;
						}
						else if ($wp_version[0] == '1.1')
						{
							echo new JResponseJson($response = null, $message = JText::_('COM_J2XML_MSG_FILE_FORMAT_J2XMLWP'), $error = true, $ignoreMessages = false);
							$this->app->close();
							return false;
						}
						else
						{
							echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
							$this->app->close();
							return false;
						}
					}
					else
					{
						echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
						$this->app->close();
						return false;
					}
				}
				else
				{
					echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
					$this->app->close();
					return false;
				}
			}
			else
			{
				echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
				$this->app->close();
				return false;
			}
		}
		elseif (strtoupper($xml->getName()) == 'HTML')
		{
			echo new JResponseJson($response = null, $message = JText::_('COM_J2XML_MSG_FILE_FORMAT_J2XMLHTML'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}
		else
		{
			echo new JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true, $ignoreMessages = false);
			$this->app->close();
			return false;
		}
	
		JLog::add(new JLogEntry(print_r($this->app->getMessageQueue(), true), JLog::DEBUG, 'com_j2xml'));
		
		echo new JResponseJson($response = null, $message = $this->app->getMessageQueue(), $error = false, $ignoreMessages = false);
		$this->app->close();	
	}
}