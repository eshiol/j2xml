<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.2.14
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml;

// no direct access
defined('_JEXEC') or die('Restricted access.');

// Import filesystem libraries.
\JLoader::import('joomla.filesystem.file');
\JLoader::import('joomla.log.log');
\JLoader::import('eshiol.J2xml.Table');
\JLoader::import('eshiol.J2xml.Version');

use eshiol\J2xml\Table\Category;
use eshiol\J2xml\Table\Contact;
use eshiol\J2xml\Table\Content;
use eshiol\J2xml\Table\Field;
use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Menu;
use eshiol\J2xml\Table\Menutype;
use eshiol\J2xml\Table\Module;
use eshiol\J2xml\Table\User;
use eshiol\J2xml\Table\Usernote;
use eshiol\J2xml\Table\Viewlevel;
use eshiol\J2xml\Table\Weblink;
use eshiol\J2xml\Version;

\JLoader::import('eshiol.J2xml.Table.Category');
\JLoader::import('eshiol.J2xml.Table.Contact');
\JLoader::import('eshiol.J2xml.Table.Content');
\JLoader::import('eshiol.J2xml.Table.Field');
\JLoader::import('eshiol.J2xml.Table.Image');
\JLoader::import('eshiol.J2xml.Table.Menu');
\JLoader::import('eshiol.J2xml.Table.Menutype');
\JLoader::import('eshiol.J2xml.Table.Module');
\JLoader::import('eshiol.J2xml.Table.User');
\JLoader::import('eshiol.J2xml.Table.Usernote');
\JLoader::import('eshiol.J2xml.Table.Viewlevel');
\JLoader::import('eshiol.J2xml.Table.Weblink');
\JLoader::import('eshiol.J2xml.Version');

/**
 *
 * Exporter
 *
 */
class Exporter
{

	// images/stories is path of the images of the sections and categories hard
	// coded in the file \libraries\joomla\html\html\list.php at the line 52
	private $_image_path = "images";

	private $_admin = 'admin';

	private $_option = '';

	/**
	 * CONSTRUCTOR
	 *
	 * @since 1.5
	 */
	function __construct ()
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$db         = \JFactory::getDbo();
		$version    = new \JVersion();

		$this->_option = (PHP_SAPI != 'cli') ? \JFactory::getApplication()->input->getCmd('option') : 'cli_' .
				 strtolower(get_class(\JApplicationCli::getInstance()));

		// Merge the default translation with the current translation
		if ($version->isCompatible('3.2'))
		{
			$jlang = \JFactory::getApplication()->getLanguage();
		}
		else
		{
			$jlang = \JFactory::getLanguage();
		}
		$jlang->load('lib_j2xml', JPATH_SITE, 'en-GB', true);
		$jlang->load('lib_j2xml', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('lib_j2xml', JPATH_SITE, null, true);

		try {
			$db->truncateTable("#__j2xml_usergroups");

			$query = $db->getQuery(true)
			//	->insert($db->quoteName("#__j2xml_usergroups"))
				->select($db->quoteName("id"))
				->select($db->quoteName("parent_id"))
				->select("CONCAT('[\"',REPLACE(" . $db->quoteName("title") . ",'\"','\\\"'),'\"]')")
				->from($db->quoteName("#__usergroups"));
			$query = "INSERT INTO " . $db->quoteName("#__j2xml_usergroups") . $query;
			$db->setQuery($query)->execute();

			do {
				$query = $db->getQuery(true)
					->update($db->quoteName("#__j2xml_usergroups", "j"))
					->join("INNER", $db->quoteName("#__usergroups", "g"), $db->quoteName("j.parent_id") . " = " . $db->quoteName("g.id"))
					->set($db->quoteName("j.parent_id") . " = " . $db->quoteName("g.parent_id"))
					->set($db->quoteName("j.title") . " = CONCAT('[\"',REPLACE(" . $db->quoteName("g.title") . ",'\"','\\\"'), '\",', SUBSTR(" . $db->quoteName("j.title") . ",2))");
				$db->setQuery($query)->execute();

				$query = $db->getQuery(true)
					->select("COUNT(*)")
					->from($db->quoteName("#__j2xml_usergroups"))
					->where($db->quoteName("parent_id") . " > 0");
				$n = $db->setQuery($query)->loadResult();
			} while ($n > 0);
		}
		catch (\JDatabaseExceptionExecuting $e)
		{
			// If the query fails we will go on
		}
	}

	/**
	 * Init xml
	 *
	 * @return
	 * @since 18.8.309
	 */
	protected function _root ()
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$data = '<?xml version="1.0" encoding="UTF-8" ?>';
		// $data .= Version::$DOCTYPE;
		$data .= '<j2xml version="' . Version::$DOCVERSION . '"/>';
		$xml = new \SimpleXMLElement($data);
		$xml->addChild('base', \JUri::root());
		return $xml;
	}

	function export ($xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if ($options['debug'] > 0)
		{
			$app = \JFactory::getApplication();
			$data = ob_get_contents();
			if ($data)
			{
				$app->enqueueMessage(\JText::_('LIB_J2XML_MSG_ERROR_EXPORT'), 'error');
				$app->enqueueMessage($data, 'error');
				return false;
			}
		}
		ob_clean();

		$version = explode(".", Version::$DOCVERSION);
		$xmlVersionNumber = $version[0] . $version[1] . substr('0' . $version[2], strlen($version[2]) - 1);

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();

		// modify the MIME type
		$document = \JFactory::getDocument();

		// Verify that the server supports gzip compression before we attempt to gzip encode the data.
		// @codeCoverageIgnoreStart
		if (!\extension_loaded('zlib') || ini_get('zlib.output_compression'))
		{
			$document->setMimeEncoding('text/xml', true);
			\JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml' . $xmlVersionNumber . date('YmdHis') . '.xml"', true);
		}
		elseif (!empty($options['gzip']) || !empty($options['compress']))
		{
			$document->setMimeEncoding('application/gzip', true);
			\JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml' . $xmlVersionNumber . date('YmdHis') . '.gz"', true);
			$data = gzencode($data, 4);
		}
		else
		{
			$document->setMimeEncoding('text/xml', true);
			\JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml' . $xmlVersionNumber . date('YmdHis') . '.xml"', true);
		}
		echo $data;
		return true;
	}

	/**
	 * Export content articles, images, section and categories
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 1.5.2.14
	 */
	function content ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Content::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
			$this->_option . '.' . __FUNCTION__,
			&$xml,
			$params
		));

		return $xml;
	}

	/**
	 * Export categories
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 1.5.3beta5.43
	 */
	function categories ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		$options['content'] = 1;
		foreach ($ids as $id)
		{
			Category::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
			$this->_option . '.' . __FUNCTION__,
			&$xml,
			$params
		));

		return $xml;
	}

	/**
	 * Export users
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 1.5.3beta4.39
	 */
	function users ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			User::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');

		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
			$this->_option . '.' . __FUNCTION__,
			&$xml,
			$params
		));

		return $xml;
	}

	/**
	 * Export weblinks
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 1.5.3beta3.38
	 */
	function weblinks ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Weblink::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
				$this->_option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/**
	 * Export contacts
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 16.12.289
	 */
	function contact ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Contact::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
			$this->_option . '.' . __FUNCTION__,
			&$xml,
			$params
		));

		return $xml;
	}

	/**
	 * Export fields
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 17.6.299
	 */
	function fields ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Field::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
				$this->_option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/**
	 * Export viewlevels
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 19.2.323
	 */
	function viewlevels ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Viewlevel::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
				$this->_option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/**
	 * Export menu
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 22.1.355
	 */
	function menus ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		if (! $xml)
		{
			$xml = $this->_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Menutype::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
				$this->_option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/**
	 * Export modules
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 22.1.355
	 */
	function modules ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		if (! $xml)
		{
			$xml = $this->_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Module::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');
		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
				$this->_option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/**
	 * Export user notes
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 22.1.355
	 */
	function usernotes ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if (! $xml)
		{
			$xml = self::_root();
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach ($ids as $id)
		{
			Usernote::export($id, $xml, $options);
		}

		$params = new \JRegistry($options);
		\JPluginHelper::importPlugin('j2xml');

		// Trigger the onAfterExport event.
		$results = \JFactory::getApplication()->triggerEvent('onJ2xmlAfterExport', array(
			$this->_option . '.' . __FUNCTION__,
			&$xml,
			$params
		));

		return $xml;
	}
}