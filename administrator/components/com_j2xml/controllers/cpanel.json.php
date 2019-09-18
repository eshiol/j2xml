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

jimport('eshiol.j2xml.Importer');
jimport('eshiol.j2xmlpro.Importer');
jimport('cms.response.json');

require_once JPATH_ADMINISTRATOR . '/components/com_j2xml/helpers/j2xml.php';

/**
 * Controller class.
 *
 * @version 3.7.201
 * @since 3.6.160
 */
class J2XMLControllerCpanel extends JControllerLegacy
{

	/**
	 * The application object.
	 *
	 * @var JApplicationBase
	 * @since 3.6.160
	 */
	protected $app;

	function __construct ($default = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		parent::__construct();

		$this->app = JFactory::getApplication();
	}

	function import ()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$data = $this->app->input->post->get('j2xml_data', '', 'RAW');
		$filename = $this->app->input->post->get('j2xml_filename', '', 'RAW');

		// Send json mime type.
		$this->app->mimeType = 'application/json';
		$this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
		$this->app->sendHeaders();

		$dispatcher = \JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');

		$params = JComponentHelper::getParams('com_j2xml');

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

		JLog::add(new JLogEntry('data: ' . $data, JLog::DEBUG, 'com_j2xml'));
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
						$this->app->enqueueMessage($msg, 'message');
						break;
					case LIBXML_ERR_ERROR:
						$this->app->enqueueMessage($msg, 'notice');
						break;
					case LIBXML_ERR_FATAL:
						$this->app->enqueueMessage($msg, 'error');
						break;
				}
			}
			libxml_clear_errors();
		}

		if (! $xml)
		{
			echo new \JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true,
					$ignoreMessages = false);
			$this->app->close();
			return false;
		}

		$results = $dispatcher->trigger('onBeforeImport', array(
				'com_j2xml.cpanel',
				&$xml
		));

		if (! $xml)
		{
			echo new \JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true,
					$ignoreMessages = false);
			$this->app->close();
			return false;
		}
		elseif (strtoupper($xml->getName()) == 'J2XML')
		{
			if (! isset($xml['version']))
			{
				$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), 'error');
			}
			else
			{
				$iparams = new \JRegistry();

				$iparams->set('filename', $filename);
				$iparams->set('version', (string) $xml['version']);
				$iparams->set('keep_user_id', $params->get('keep_id', 0));
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
				$iparams->set('linksourcefile', $params->get('linksourcefile'));
				$iparams->set('weblinks', $params->get('import_weblinks'));
				$iparams->set('keep_frontpage', $params->get('keep_frontpage'));
				$iparams->set('keep_rating', $params->get('keep_rating'));

				if ($params->get('keep_category', 1) == 2)
				{
					$iparams->set('content_category_forceto', $params->get('category'));
				}

				$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
				// set_time_limit(120);

				try
				{
					$importer->import($xml, $iparams);
				}
				catch (\Exception $ex)
				{
					JLog::add(JText::sprintf('LIB_J2XML_MSG_USERGROUP_ERROR', $ex->getMessage()), JLog::ERROR, 'lib_j2xml');
					$this->app->redirect('index.php?option=com_j2xml');
					return;
				}
			}
		}
/**
		if (! $xml)
		{
			echo new \JResponseJson($response = null, $message = JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), $error = true,
					$ignoreMessages = false);
			$this->app->close();
			return false;
		}
		else
		{
			$params->set('filename', $filename);

			// set_time_limit(120);
			$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
			$importer->import($xml, $params);
		}
*/
		JLog::add(new JLogEntry(print_r($this->app->getMessageQueue(), true), JLog::DEBUG, 'com_j2xml'));

		echo new \JResponseJson($response = null, $message = $this->app->getMessageQueue(), $error = false, $ignoreMessages = false);
		$this->app->close();
	}
}