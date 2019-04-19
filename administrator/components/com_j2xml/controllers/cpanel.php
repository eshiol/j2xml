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

JLoader::import('eshiol.j2xml.Importer');
JLoader::import('eshiol.j2xmlpro.Importer');

require_once JPATH_ADMINISTRATOR . '/components/com_j2xml/helpers/j2xml.php';

/**
 * Controller class.
 *
 * @version 3.7.195
 * @since 1.5.3
 */
class J2xmlControllerCpanel extends JControllerLegacy
{

	/**
	 * Custom Constructor
	 */
	function __construct ($default = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		parent::__construct($default);
	}

	public function display ($cachable = false, $urlparams = false)
	{
		$this->input->set('view', 'cpanel');
		$this->input->set('layout', 'default');
		parent::display($cachable, $urlparams);
	}

	function import ()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$app = JFactory::getApplication('administrator');
		if (isset($_SERVER["CONTENT_LENGTH"]))
		{
			if ($_SERVER["CONTENT_LENGTH"] > ((int) ini_get('post_max_size') * 1024 * 1024))
			{
				$app->enqueueMessage(JText::_('COM_J2XML_ERROR_WARNUPLOADTOOLARGE'), 'error');
				$this->setRedirect('index.php?option=com_j2xml');
				return false;
			}
		}

		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');

		$msg = '';
		$db = JFactory::getDBO();
		$date = JFactory::getDate();
		$now = $date->toSQL();
		$params = JComponentHelper::getParams('com_j2xml');
		$this->setRedirect('index.php?option=com_j2xml');
		libxml_use_internal_errors(true);
		$filetype = $this->input->get('j2xml_filetype', 1);
		switch ($filetype)
		{
			case 1:
				// Retrieve file details from uploaded file, sent from upload
				// form:
				$file = $this->input->files->get('j2xml_local', null, 'array');
				if (! isset($file))
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'), 'error');
					return false;
				}
				elseif ($file['error'] > 0)
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'), 'error');
					return false;
				}
				$filename = $file['tmp_name'];
				$x = explode(".", $file['name']);
				$extn = end($x);
				break;
			case 2:
				if (! ($filename = $this->input->get('j2xml_url', null, 'URL')))
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'), 'error');
					return false;
				}
				$x = explode(".", $filename);
				$extn = end($x);
				break;
			case 3:
				if ($filename = $this->input->get('j2xml_server', null))
				{
					$filename = JPATH_ROOT . '/' . $filename;
				}
				else
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'), 'error');
					return false;
				}
				$x = explode(".", $filename);
				$extn = end($x);
				break;
			default:
				$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'), 'error');
				return false;
		}
		if (! ($data = implode(gzfile($filename))))
		{
			$data = file_get_contents($filename);
		}
		$data = mb_convert_encoding($data, 'UTF-8');

		$dispatcher = \JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');

		$results = $dispatcher->trigger('onContentPrepareData', array(
				'com_j2xml.cpanel',
				&$data
		));
		$data = strstr($data, '<?xml version="1.0" ');

		$data = J2XMLHelper::stripInvalidXml($data);
		if (! defined('LIBXML_PARSEHUGE'))
		{
			define(LIBXML_PARSEHUGE, 524288);
		}
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);

		if (! $xml)
		{
			$errors = libxml_get_errors();
			foreach ($errors as $error)
			{
				$msg = $error->code . ' - ' . $error->message . ' at line ' . $error->line;
				switch ($error->level)
				{
					default:
					case LIBXML_ERR_WARNING:
						$app->enqueueMessage($msg, 'message');
						break;
					case LIBXML_ERR_ERROR:
						$app->enqueueMessage($msg, 'notice');
						break;
					case LIBXML_ERR_FATAL:
						$app->enqueueMessage($msg, 'error');
						break;
				}
			}
			libxml_clear_errors();
			$this->setRedirect('index.php?option=com_j2xml');
		}

		if (! $xml)
		{
			$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), 'error');
			return false;
		}

		$results = $dispatcher->trigger('onBeforeImport', array(
				'com_j2xml.cpanel',
				&$xml
		));

		if (! $xml)
		{
			$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), 'error');
		}
		else
		{
			$xmlVersion = $xml['version'];
			$version = explode(".", $xmlVersion);
			$xmlVersionNumber = $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);
			
			$j2xmlVersion = class_exists('eshiol\J2xmlpro\Version') ? eshiol\J2xmlpro\Version::$DOCVERSION : eshiol\J2xml\Version::$DOCVERSION;
			$version = explode(".", $j2xmlVersion);
			$j2xmlVersionNumber = $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);
			
			if (($xmlVersionNumber == $j2xmlVersionNumber) || ($xmlVersionNumber == "150900") || ($xmlVersionNumber == "120500")) 
			{				
				$iparams = new \JRegistry();
				$iparams->set('filename', $filename);
				$iparams->set('version', (string) $xml['version']);

				$iparams->set('categories', $params->get('import_categories', 1));
				$iparams->set('contacts', $params->get('import_contacts', 1));
				$iparams->set('fields', $params->get('import_fields', 1));
				$iparams->set('images', $params->get('import_images', 1));
	
				$iparams->set('keep_id', $params->get('keep_id', 0));
				$iparams->set('tags', $params->get('import_tags', 1));
				$iparams->set('users', $params->get('import_users', 1));
				$iparams->set('superusers', $params->get('import_superusers', 0));
				$iparams->set('usernotes', $params->get('import_usernotes', 1));
				$iparams->set('viewlevels', $params->get('import_viewlevels', 1));
				$iparams->set('content', $params->get('import_content'));
				$iparams->set('logger', 'xmlrpc');
	
				if ($params->get('keep_category', 1) == 2)
				{
					$iparams->set('content_category_forceto', $params->get('category'));
				}
	
				$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
				// set_time_limit(120);
				$importer->import($xml, $iparams);
			}
			else
			{
				$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion),'error');
			}
		}
		$this->setRedirect('index.php?option=com_j2xml');
	}
}