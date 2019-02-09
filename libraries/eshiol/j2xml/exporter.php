<?php
/**
 * @package		J2XML Pro
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2XML;

// no direct access
defined('_JEXEC') or die('Restricted access.');

// Import filesystem libraries.
jimport('joomla.filesystem.file');
jimport('joomla.log.log');
jimport('eshiol.j2xml.Table');
jimport('eshiol.j2xml.Version');

use eshiol\J2XML\Table\Category;
use eshiol\J2XML\Table\Contact;
use eshiol\J2XML\Table\Content;
use eshiol\J2XML\Table\Field;
use eshiol\J2XML\Table\Image;
use eshiol\J2XML\Table\User;
use eshiol\J2XML\Table\Usernote;
use eshiol\J2XML\Table\Viewlevel;
use eshiol\J2XML\Table\Weblink;
use eshiol\J2XML\Version;




use Joomla\Registry\Registry;
\JLoader::import('eshiol.j2xml.Table.Category');
\JLoader::import('eshiol.j2xml.Table.Contact');
\JLoader::import('eshiol.j2xml.Table.Content');
\JLoader::import('eshiol.j2xml.Table.Field');
\JLoader::import('eshiol.j2xml.Table.Image');
\JLoader::import('eshiol.j2xml.Table.User');
\JLoader::import('eshiol.j2xml.Table.Usernote');
\JLoader::import('eshiol.j2xml.Table.Viewlevel');
\JLoader::import('eshiol.j2xml.Table.Weblink');
\JLoader::import('eshiol.j2xml.Version');

/**
 * Exporter
 * 
 * @author Helios Ciancio
 *        
 * @version 19.2.318
 * @since 1.5.2.14
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
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$this->option = (PHP_SAPI != 'cli') ? \JFactory::getApplication()->input->getCmd('option') : 'cli_' .
				 strtolower(get_class(\JApplicationCli::getInstance()));
		$this->_db = \JFactory::getDbo();
	}

	/*
	 * Init xml
	 * @return
	 * @since 18.8.309
	 */
	protected function _root ()
	{
		$data = '<?xml version="1.0" encoding="UTF-8" ?>';
		// $data .= Version::$DOCTYPE;
		$data .= '<j2xml version="' . Version::$DOCVERSION . '"/>';
		$xml = new \SimpleXMLElement($data);
		$xml->addChild('base', \JUri::root());
		return $xml;
	}

	function export ($xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

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
		if ($options['gzip'])
		{
			$document->setMimeEncoding('application/gzip-compressed', true);
			\JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml' . $xmlVersionNumber . date('YmdHis') . '.gz"', true);
			$data = gzencode($data, 9);
		}
		else
		{
			$document->setMimeEncoding('application/xml', true);
			\JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml' . $xmlVersionNumber . date('YmdHis') . '.xml"', true);
		}
		echo $data;
		return true;
	}

	/*
	 * Export content articles, images, section and categories
	 * @return xml string
	 * @since 1.5.2.14
	 */
	function content ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

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

		$params = new Registry($options);
		\JPluginHelper::importPlugin('j2xml');
		$dispatcher = \JEventDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array(
				$this->option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/*
	 * Export categories
	 * @return xml string
	 * @since 1.5.3beta5.43
	 */
	function categories ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

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

		$params = new Registry($options);
		\JPluginHelper::importPlugin('j2xml');
		$dispatcher = \JEventDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array(
				$this->option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/*
	 * Export users
	 * @return xml string
	 * @since 1.5.3beta4.39
	 */
	function users ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

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

		$params = new Registry($options);
		\JPluginHelper::importPlugin('j2xml');
		$dispatcher = \JEventDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array(
				$this->option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/*
	 * Export weblinks
	 * @return xml string
	 * @since 1.5.3beta3.38
	 */
	function weblinks ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

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

		$params = new Registry($options);
		\JPluginHelper::importPlugin('j2xml');
		$dispatcher = \JEventDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array(
				$this->option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}

	/**
	 * Export weblink
	 *
	 * @param string $_image
	 *        	Image name
	 * @param SimpleXMLElement $xml
	 *        	xml
	 * @param array $options
	 *        	options
	 * @throws
	 * @return void
	 * @since 15.8.257
	 */
	private function _weblink ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('id: ' . $id, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.Table.Weblink');
		$item = JTable::getInstance('weblink', 'eshTable');
		if (! $item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/weblink/id[text() = '" . $id . "']"))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['users'])
		{
			if ($item->created_by)
			{
				\eshiol\J2XML\Table\User::export($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				\eshiol\J2XML\Table\User::export($item->modified_by, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = $item->description;
			$_image = preg_match_all($this->_image_match_string, $text, $matches, PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i ++)
				{
					if ($_image = $matches[1][$i])
					{
						\eshiol\J2XML\Table\Image::export($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image_first))
				{
					\eshiol\J2XML\Table\Image::export($imgs->image_first, $xml, $options);
				}
				if (isset($imgs->image_second))
				{
					\eshiol\J2XML\Table\Image::export($imgs->image_second, $xml, $options);
				}
			}
		}

		if ($options['categories'] && ($item->catid > 0))
			self::_category($item->catid, $xml, $options);

		// if (class_exists('JHelperTags'))
		// {
		$htags = new JHelperTags();
		$itemtags = $htags->getItemTags('com_weblinks.weblink', $id);
		foreach ($itemtags as $itemtag)
		{
			self::_tag($itemtag->tag_id, $xml, $options);
		}
		// }

		return $xml;
	}

	/**
	 * Export button
	 *
	 * @param int $id
	 *        	the id
	 * @param SimpleXMLElement $xml
	 *        	xml
	 * @param array $options
	 *        	options
	 * @throws
	 * @return void
	 * @since 16.1.275
	 */
	private function _buttons ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('id: ' . $id, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.Table.button');
		$item = JTable::getInstance('button', 'eshTable');
		if (! $item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/button/id[text() = '" . $id . "']"))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['users'])
		{
			if ($item->created_by)
			{
				\eshiol\J2XML\Table\User::export($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				\eshiol\J2XML\Table\User::export($item->modified_by, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = $item->description;
			$_image = preg_match_all($this->_image_match_string, $text, $matches, PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i ++)
				{
					if ($_image = $matches[1][$i])
					{
						\eshiol\J2XML\Table\Image::export($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image))
				{
					\eshiol\J2XML\Table\Image::export($imgs->image, $xml, $options);
				}
			}
		}

		if ($options['categories'] && ($item->catid > 0))
		{
			self::_category($item->catid, $xml, $options);
		}

		// if (class_exists('JHelperTags'))
		// {
		$htags = new JHelperTags();
		$itemtags = $htags->getItemTags('com_buttons.button', $id);
		foreach ($itemtags as $itemtag)
		{
			self::_tag($itemtag->tag_id, $xml, $options);
		}
		// }

		return $xml;
	}

	/*
	 * Export contacts
	 * @return xml string
	 * @since 16.12.289
	 */
	function contact ($ids, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

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

		$params = new Registry($options);
		\JPluginHelper::importPlugin('j2xml');
		$dispatcher = \JEventDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array(
				$this->option . '.' . __FUNCTION__,
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
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('ids: ' . print_r($ids, true), \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

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

		$params = new Registry($options);
		\JPluginHelper::importPlugin('j2xml');
		$dispatcher = \JEventDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array(
				$this->option . '.' . __FUNCTION__,
				&$xml,
				$params
		));

		return $xml;
	}
}