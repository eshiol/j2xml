<?php
/**
 * @version		17.6.299 libraries/eshiol/j2xml/exporter.php
*
* @package		J2XML
* @subpackage	lib_j2xml
* @since		1.5.2.14
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

//Import filesystem libraries.
jimport('joomla.filesystem.file');
jimport('joomla.log.log');
jimport('eshiol.j2xml.table');
jimport('eshiol.j2xml.version');

use Joomla\Registry\Registry;

class J2XMLExporter
{
	private $_image_match_string = '/<img.*?src="([^"]*)".*?[^>]*>/s';
	// images/stories is path of the images of the sections and categories hard coded in the file \libraries\joomla\html\html\list.php at the line 52
	private $_image_path = "images";
	private $_admin = 'admin';

	private $_option = '';

	/**
	 * CONSTRUCTOR
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */
	function __construct()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

		$db = JFactory::getDBO();
		$db->setQuery("
					CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
						`id` int(10) unsigned NOT NULL,
						`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
						`title` varchar(100) NOT NULL DEFAULT ''
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
					")->execute();
		$db->setQuery("
					TRUNCATE TABLE
						`#__j2xml_usergroups`;
					")->execute();
		$db->setQuery("
					INSERT INTO
						`#__j2xml_usergroups`
					SELECT
						`id`,`parent_id`,CONCAT('[\"',REPLACE(`title`,'\"','\\\"'),'\"]')
					FROM
						`#__usergroups`;
					")->execute();
		do {
			$db->setQuery("
					UPDATE
						`#__j2xml_usergroups` j
					INNER JOIN
						`#__usergroups` g
					ON
						j.parent_id = g.id
					SET
						j.parent_id = g.parent_id,
						j.title = CONCAT('[\"',REPLACE(`g`.`title`,'\"','\\\"'), '\",', SUBSTR(`j`.`title`,2));
					")->execute();
			$n = $db->setQuery("
					SELECT
						COUNT(*)
					FROM
						`#__j2xml_usergroups`
					WHERE
						`parent_id` > 0;
					")->loadResult();
		} while ($n > 0);

		$this->option = (PHP_SAPI != 'cli') ? JFactory::getApplication()->input->getCmd('option') : 'cli_'.strtolower(get_class(JApplicationCli::getInstance()));
	}

	/*
	 * Export user
	 * @return
	 * @since		15.8.253
	 */
	private function _user($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/user/id[text() = '".$id."']"))
		{
			return;
		}

		jimport('eshiol.j2xml.table.user');
		$item = JTable::getInstance('user','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['contacts'])
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
			->select('id')
			->from('#__contact_details')
			->where('user_id = '.$id);
			$db->setQuery($query);

			$ids_contact = $db->loadColumn();
			foreach ($ids_contact as $id_contact)
			{
				self::_contact($id_contact, $xml, $options);
			}
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
		->select('id')
		->from('#__user_notes')
		->where('user_id = '.$id);
		$db->setQuery($query);

		$ids_usernote = $db->loadColumn();
		foreach ($ids_usernote as $id_usernote)
		{
			self::_usernote($id_usernote, $xml, $options);
		}
	}


	/**
	 * Export user
	 *
	 * @param	string	$_image  Image name
	 * @param	SimpleXMLElement	$xml	xml
	 * @param	array	$options	options
	 * @throws
	 * @return	void
	 * @since		15.8.253
	 */
	private function _image($_image, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('image: '.$_image, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if($xml->xpath("//j2xml/img[@src = '".htmlentities($_image, ENT_QUOTES, "UTF-8")."']"))
			return;

			//$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, urldecode($_image));
			$file_path = JPATH_SITE.DIRECTORY_SEPARATOR.urldecode($_image);
			if (JFile::exists($file_path))
			{
				$img = $xml->addChild('img', base64_encode(file_get_contents($file_path)));
				$img->addAttribute('src', htmlentities($_image, ENT_QUOTES, "UTF-8"));
				JLog::add(new JLogEntry('image added: '.$_image, JLOG::DEBUG, 'lib_j2xml'));
			}
	}

	/**
	 * Export user
	 *
	 * @param	string	$_image  Image name
	 * @param	SimpleXMLElement	$xml	xml
	 * @param	array	$options	options
	 * @throws
	 * @return	void
	 * @since		15.8.253
	 */
	private function _content($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/content/id[text() = '".$id."']"))
		{
			return;
		}

		jimport('eshiol.j2xml.table.content');
		$item = JTable::getInstance('content', 'eshTable');
		if (!$item->load($id))
		{
			return;
		}

		$params = new JRegistry($options);
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');
		$results = $dispatcher->trigger('onBeforeContentExport', array('lib_j2xml.article', &$item, $params));

		if ($options['users'])
		{
			if ($item->created_by)
			{
				self::_user($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				self::_user($item->modified_by, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = $item->introtext.$item->fulltext;
			$_image = preg_match_all($this->_image_match_string,$text,$matches,PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i++)
				{
					if ($_image = $matches[1][$i])
					{
						self::_image($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image_fulltext))
				{
					self::_image($imgs->image_fulltext, $xml, $options);
				}

				if (isset($imgs->image_intro))
				{
					self::_image($imgs->image_intro, $xml, $options);
				}
			}
		}

		if ($options['categories'] && ($item->catid > 0))
		{
			self::_category($item->catid, $xml, $options);
		}

		if (class_exists('JHelperTags'))
		{
			$htags = new JHelperTags;
			$itemtags = $htags->getItemTags('com_content.article', $id);
			foreach ($itemtags as $itemtag)
			{
				self::_tag($itemtag->tag_id, $xml, $options);
			}
		}

		// export fields
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('DISTINCT field_id')
			->from('#__fields_values')
			->where('item_id = '.$id);
		$db->setQuery($query);

		$ids_field = $db->loadColumn();
		foreach ($ids_field as $id_field)
		{
			self::_field($id_field, $xml, $options);
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		return $xml;
	}

	/*
	 * Export tag
	 * @return 		xml string
	 * @since		14.8.240
	 */
	private function _tag($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.tag');
		$item = JTable::getInstance('tag', 'eshTable');
		if (!$item->load($id))
			return;

			if ($xml->xpath("//j2xml/tag/id[text() = '".$id."']"))
			{
				return;
			}
			if ($item->parent_id > 1)
			{
				self::_tag($item->parent_id, $xml, $options);
			}

			$doc = dom_import_simplexml($xml)->ownerDocument;
			$fragment = $doc->createDocumentFragment();
			$fragment->appendXML($item->toXML());
			$doc->documentElement->appendChild($fragment);

			if ($options['images'])
			{
				$text = html_entity_decode($item->description);
				$_image = preg_match_all($this->_image_match_string,$text,$matches,PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0)
				{
					for ($i = 0; $i < count($matches[1]); $i++)
					{
						if ($_image = $matches[1][$i])
						{
							self::_image($_image.'1', $xml, $options);
						}
					}
				}
				if ($imgs = json_decode($item->images))
				{
					if (isset($imgs->image_fulltext))
					{
						self::_image($imgs->image_fulltext, $xml, $options);
					}

					if (isset($imgs->image_intro))
					{
						self::_image($imgs->image_intro, $xml, $options);
					}
				}
			}

			if ($options['users'] && $item->created_user_id)
			{
				self::_user($item->created_user_id, $xml, $options);
			}

			if ($options['users'] && $item->modified_user_id)
			{
				self::_user($item->modified_user_id, $xml, $options);
			}

			return $xml;
	}


	/*
	 * Export category
	 * @return 		xml string
	 * @since		1.6.1.60
	 */
	private function _category($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/category/id[text() = '".$id."']"))
		{
			return;
		}

		jimport('eshiol.j2xml.table.category');
		$item = JTable::getInstance('category', 'eshTable');
		if (!$item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$allowed_extensions = array('com_content','com_buttons');
		if (in_array($item->extension, $allowed_extensions))
		{
			if (isset($options['content']) && $options['content'])
			{
				$table = '#__'.substr($item->extension, 4);
				$extension = '_'.substr($item->extension, 4);
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
				->select('id')
				->from($table)
				->where('catid = '.$id);
				$db->setQuery($query);
				$ids_content = $db->loadColumn();
				$options['categories'] = 0;
				foreach ($ids_content as $id_content)
				{
					self::$extension($id_content, $xml, $options);
				}
			}
		}
		$options['content'] = 0;

		if ($item->parent_id > 1)
		{
			self::_category($item->parent_id, $xml, $options);
		}

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['users'] && $item->created_user_id)
		{
			self::_user($item->created_user_id, $xml, $options);
		}

		if ($options['users'] && $item->modified_user_id)
		{
			self::_user($item->modified_user_id, $xml, $options);
		}

		if ($item->access > 6)
		{
			self::_viewlevel($item->access, $xml, $options);
		}

		if ($options['images'])
		{
			$img = null;
			$text = html_entity_decode($item->description);
			$_image = preg_match_all($this->_image_match_string,$text,$matches,PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i++)
				{
					if ($_image = $matches[1][$i])
					{
						self::_image($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->params))
			{
				if (isset($imgs->image))
				{
					self::_image($imgs->image, $xml, $options);
				}
			}
		}

		if (class_exists('JHelperTags'))
		{
			$htags = new JHelperTags;
			$itemtags = $htags->getItemTags($item->extension.'.category', $id);
			foreach ($itemtags as $itemtag)
			{
				self::_tag($itemtag->tag_id, $xml, $options);
			}
		}
	}

	function export($xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

		if ($options['debug'] > 0)
		{
			$app = JFactory::getApplication();
			$data = ob_get_contents();
			if ($data)
			{
				$app->enqueueMessage(JText::_('LIB_J2XML_MSG_ERROR_EXPORT'), 'error');
				$app->enqueueMessage($data, 'error');
				return false;
			}
		}
		ob_clean();

		$version = explode(".", J2XMLVersion::$DOCVERSION);
		$xmlVersionNumber = $version[0].$version[1].substr('0'.$version[2], strlen($version[2])-1);

		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();

		// modify the MIME type
		$document = JFactory::getDocument();
		if ($options['gzip'])
		{
			$document->setMimeEncoding('application/gzip-compressed', true);
			JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml'.$xmlVersionNumber.date('YmdHis').'.gz"', true);
			$data = gzencode($data, 9);
		}
		else
		{
			$document->setMimeEncoding('application/xml', true);
			JResponse::setHeader('Content-disposition', 'attachment; filename="j2xml'.$xmlVersionNumber.date('YmdHis').'.xml"', true);
		}
		echo $data;
		return true;
	}

	/*
	 * Export content articles, images, section and categories
	 * @return 		xml string
	 * @since		1.5.2.14
	 */
	function content($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_content($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/*
	 * Export categories
	 * @return 		xml string
	 * @since		1.5.3beta5.43
	 */
	function categories($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		$options['content'] = 1;
		foreach($ids as $id)
		{
			self::_category($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/*
	 * Export users
	 * @return 		xml string
	 * @since		1.5.3beta4.39
	 */
	function users($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_user($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/*
	 * Export weblinks
	 * @return 		xml string
	 * @since		1.5.3beta3.38
	 */
	function weblinks($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_weblink($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/**
	 * Export weblink
	 *
	 * @param	string	$_image  Image name
	 * @param	SimpleXMLElement	$xml	xml
	 * @param	array	$options	options
	 * @throws
	 * @return	void
	 * @since	15.8.257
	 */
	private function _weblink($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.weblink');
		$item = JTable::getInstance('weblink', 'eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/weblink/id[text() = '".$id."']"))
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
				self::_user($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				self::_user($item->modified_by, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = $item->description;
			$_image = preg_match_all($this->_image_match_string,$text,$matches,PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i++)
				{
					if ($_image = $matches[1][$i])
					{
						self::_image($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image_first))
				{
					self::_image($imgs->image_first, $xml, $options);
				}
				if (isset($imgs->image_second))
				{
					self::_image($imgs->image_second, $xml, $options);
				}
			}
		}

		if ($options['categories'] && ($item->catid > 0))
			self::_category($item->catid, $xml, $options);

			if (class_exists('JHelperTags'))
			{
				$htags = new JHelperTags;
				$itemtags = $htags->getItemTags('com_weblinks.weblink', $id);
				foreach ($itemtags as $itemtag)
				{
					self::_tag($itemtag->tag_id, $xml, $options);
				}
			}

			return $xml;
	}

	/*
	 * Export contact
	 * @return
	 * @since		15.9.261
	 */
	private function _contact($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.contact');
		$item = JTable::getInstance('contact','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/contact/id[text() = '".$item->id."']"))
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
				self::_user($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				self::_user($item->modified_by, $xml, $options);
			}
		}
		if ($options['images'])
		{
			if (isset($item->image))
			{
				self::_image($item->image, $xml, $options);
			}
		}

		if ($options['categories'] && ($item->catid > 0))
		{
			self::_category($item->catid, $xml, $options);
		}

		if (class_exists('JHelperTags'))
		{
			$htags = new JHelperTags;
			$itemtags = $htags->getItemTags('com_contact.contact', $id);
			foreach ($itemtags as $itemtag)
			{
				self::_tag($itemtag->tag_id, $xml, $options);
			}
		}
	}

	/*
	 * Export category
	 * @return 		xml string
	 * @since		1.6.1.60
	 */
	private function _viewlevel($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/viewlevel/id[text() = '".$id."']"))
		{
			return;
		}

		jimport('eshiol.j2xml.table.viewlevel');
		$item = JTable::getInstance('viewlevel', 'eshTable');
		if (!$item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
	}

	/**
	 * Export button
	 *
	 * @param	int					$id		the id
	 * @param	SimpleXMLElement	$xml	xml
	 * @param	array				$options	options
	 * @throws
	 * @return	void
	 * @since	16.1.275
	 */
	private function _buttons($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.button');
		$item = JTable::getInstance('button', 'eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/button/id[text() = '".$id."']"))
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
				self::_user($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				self::_user($item->modified_by, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = $item->description;
			$_image = preg_match_all($this->_image_match_string,$text,$matches,PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i++)
				{
					if ($_image = $matches[1][$i])
					{
						self::_image($_image, $xml, $options);
					}
				}
			}

			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image))
				{
					self::_image($imgs->image, $xml, $options);
				}
			}
		}

		if ($options['categories'] && ($item->catid > 0))
			self::_category($item->catid, $xml, $options);

			if (class_exists('JHelperTags'))
			{
				$htags = new JHelperTags;
				$itemtags = $htags->getItemTags('com_buttons.button', $id);
				foreach ($itemtags as $itemtag)
				{
					self::_tag($itemtag->tag_id, $xml, $options);
				}
			}

			return $xml;
	}

	/*
	 * Export user note
	 * @return
	 * @since		16.1.276
	 */
	private function _usernote($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.usernote');
		$item = JTable::getInstance('usernote','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/usernote/id[text() = '".$item->id."']"))
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
				self::_user($item->created_user_id, $xml, $options);
			}
			if ($item->modified_by)
			{
				self::_user($item->modified_user_id, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = html_entity_decode($item->body);
			$_image = preg_match_all($this->_image_match_string,$text,$matches,PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i++)
				{
					if ($_image = $matches[1][$i])
					{
						self::_image($_image, $xml, $options);
					}
				}
			}
		}

		if ($options['categories'] && ($item->catid > 0))
		{
			self::_category($item->catid, $xml, $options);
		}
	}

	/*
	 * Export contacts
	 * @return 		xml string
	 * @since		16.12.289
	 */
	function contact($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_contact($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/**
	 * Export menu
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 17.1.294
	 */
	private function _menu($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.menu');
		$item = JTable::getInstance('menu','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/menu/id[text() = '".$item->id."']"))
		{
			return;
		}

		$args = array();
		parse_str(parse_url($item->link, PHP_URL_QUERY), $args);

		if (isset($args['option']) && ($args['option'] == 'com_content'))
		{
			if (isset($args['view']) && ($args['view'] == 'article'))
			{
				$this->_content($args['id'], $xml, $options);
			}
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
	}

	/**
	 * Export menutype
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 17.1.294
	 */
	private function _menutype($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.menutype');
		$item = JTable::getInstance('menutype','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/menutype/id[text() = '".$item->id."']"))
		{
			return;
		}

		$db = JFactory::getDbo();

		/* export modules */
		$modules =
			$db->setQuery(
				$db->getQuery(true)
				->select($db->qn('id'))
				->select($db->qn('params'))
				->from($db->qn('#__modules'))
				->where($db->qn('module').' = '.$db->q('mod_menu'))
				)->loadObjectList();

		foreach ($modules as $module)
		{
			$params = new Registry($module->params);
			if ($params->get('menutype') == $item->menutype)
			{
				self::_module($module->id, $xml, $options);
			}
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		/* export menus */
		$ids_menu =
			$db->setQuery(
				$db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__menu'))
				->where($db->qn('menutype').' = '.$db->q($item->menutype))
				)->loadColumn();

				foreach ($ids_menu as $id_menu)
				{
					self::_menu($id_menu, $xml, $options);
				}
	}

	/**
	 * Export menutypes
	 *
	 * @param array $ids
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 17.1.294
	 */
	function menus($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_menutype($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/**
	 * Export module
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 17.1.296
	 */
	private function _module($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.module');
		$item = JTable::getInstance('module','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/module/id[text() = '".$item->id."']"))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
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
	 * @since 17.1.296
	 */
	function modules($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_module($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}

	/**
	 * Export fields
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 17.6.299
	 */
	private function _field($id, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		jimport('eshiol.j2xml.table.field');
		$item = JTable::getInstance('field','eshTable');
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/field/id[text() = '".$item->id."']"))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
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
	function fields($ids, &$xml, $options)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('ids: '.print_r($ids, true), JLOG::DEBUG, 'lib_j2xml'));
		JLog::add(new JLogEntry('options: '.print_r($options, true), JLOG::DEBUG, 'lib_j2xml'));

		if (!$xml)
		{
			$data = '<?xml version="1.0" encoding="UTF-8" ?>';
			//			$data .= J2XMLVersion::$DOCTYPE;
			$data .= '<j2xml version="'.J2XMLVersion::$DOCVERSION.'"/>';
			$xml = new SimpleXMLElement($data);
		}

		if (is_scalar($ids))
		{
			$id = $ids;
			$ids = array();
			$ids[] = $id;
		}

		foreach($ids as $id)
		{
			self::_field($id, $xml, $options);
		}

		$params = new JRegistry($options);
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterExport event.
		$dispatcher->trigger('onAfterExport', array($this->option.'.'.__FUNCTION__, &$xml, $params));

		return $xml;
	}
}