<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       14.8.240
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
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\User;
use Joomla\Component\Tags\Administrator\Table\TagTable;
\JLoader::import('eshiol.J2xml.Table.Image');
\JLoader::import('eshiol.J2xml.Table.Table');
\JLoader::import('eshiol.J2xml.Table.User');

/**
 *
 * Tag Table
 *
 */
class Tag extends Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *			A database connector object
	 *
	 * @since 14.8.240
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		parent::__construct('#__tags', 'id', $db);
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'tags' 1: Yes, if not exists; 2: Yes, overwrite if
	 *			exists
	 *			@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$import_tags = $params->get('tags', 0);
		if ($import_tags == 0)
			return;

		$context = $params->get('context');
		$db = \JFactory::getDBO();
		$nullDate = $db->getNullDate();
		$userid = \JFactory::getUser()->id;

		foreach ($xml->xpath("//j2xml/tag") as $record)
		{
			self::prepareData($record, $data, $params);

			$id = $data['id'];
			$path = $data['path'];
			$i = strrpos($path, '/');
			if ($i === false)
			{
				$data['parent_id'] = 1;
			}
			else
			{
				$parent_path = substr($path, 0, $i);
				$data['parent_id'] = self::getTagId($parent_path);
			}

			$tag = $db->setQuery(
					$db->getQuery(true)
						->select(array(
							$db->quoteName('id'),
							$db->quoteName('title')
					))
						->from($db->quoteName('#__tags'))
						->where($db->quoteName('path') . ' = ' . $db->quote($data['path'])))
				->loadObject();

			if (class_exists('TagTable'))
			{ // Joomla! 4
				$table = new TagTable($db);
			}
			else
			{ // Joomla! 3.x
				\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
				$table = \JTable::getInstance('Tag', 'TagsTable');
			}

			if (! $tag || ($import_tags == 2))
			{
				if (! $tag)
				{ // new tag
					$isNew = true;
					$data['id'] = null;
				}
				else
				{ // tag already exists
					$isNew = false;
					$data['id'] = $tag->id;
				}

				$table->bind($data);
				$table->setLocation($data['parent_id'], 'last-child');

				if ($table->store())
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_TAG_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
				}
				else
				{
					\JLog::add(
							new \JLogEntry(
									\JText::sprintf('LIB_J2XML_MSG_TAG_NOT_IMPORTED', $data['title'] . ' (id = ' . $id . ')', $table->getError()),
									\JLog::ERROR, 'lib_j2xml'));
				}
			}
		}
	}

	/**
	 * Function that converts tags paths into array of ids
	 *
	 * @param array $tags
	 *			Array of tags paths
	 *
	 * @return array
	 *
	 * @since 18.8.310
	 */
	public static function convertPathsToIds ($tags)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if ($tags)
		{
			// Remove duplicates
			$tags = array_unique((array) $tags);

			$db = \JFactory::getDbo();

			$query = $db->getQuery(true)
				->select('id')
				->from('#__tags')
				->where('path IN (' . implode(',', array_map(array(
					$db,
					'quote'
			), $tags)) . ')');
			$db->setQuery($query);

			try
			{
				$ids = $db->loadColumn();
				return $ids;
			}
			catch (RuntimeException $e)
			{
				return false;
			}
		}

		return $tags;
	}

	/**
	 * Export data
	 *
	 * @param int $id
	 *			the id of the item to be exported
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param array $options
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function export ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		if ($xml->xpath("//j2xml/tag/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Tag($db);
		if (! $item->load($id))
		{
			return;
		}

		if ($item->parent_id > 1)
		{
			Tag::export($item->parent_id, $xml, $options);
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if (isset($options['users']) && $options['users'])
		{
			if ($item->created_user_id)
			{
				User::export($item->created_user_id, $xml, $options);
			}

			if ($item->modified_user_id)
			{
				User::export($item->modified_user_id, $xml, $options);
			}
		}

		if (isset($options['images']) && $options['images'])
		{
			$text = html_entity_decode($item->description);
			$_image = preg_match_all(self::IMAGE_MATCH_STRING, $text, $matches, PREG_PATTERN_ORDER);
			if (count($matches[1]) > 0)
			{
				for ($i = 0; $i < count($matches[1]); $i ++)
				{
					if ($_image = $matches[1][$i])
					{
						Image::export($_image, $xml, $options);
					}
				}
			}
			if ($imgs = json_decode($item->images))
			{
				if (isset($imgs->image_fulltext))
				{
					Image::export($imgs->image_fulltext, $xml, $options);
				}

				if (isset($imgs->image_intro))
				{
					Image::export($imgs->image_intro, $xml, $options);
				}
			}
		}

		return $xml;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::prepareData()
	 *
	 * @since 18.8.310
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$params->set('extension', 'com_tags');
		parent::prepareData($record, $data, $params);

		if (empty($data['alias']))
		{
			$data['alias'] = $data['title'];
			$data['alias'] = str_replace(' ', '-', $data['alias']);
		}

		if (! isset($data['metakey']))
		{
			$data['metakey'] = '';
		}
		if (! isset($data['metadesc']))
		{
			$data['metadesc'] = '';
		}
		if (! isset($data['description']))
		{
			$data['description'] = '';
		}
		if (! isset($data['images']))
		{
			$data['images'] = '{}';
		}
		if (! isset($data['urls']))
		{
			$data['urls'] = '{}';
		}
	}
}
