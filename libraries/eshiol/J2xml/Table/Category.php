<?php
/**
 * @package		Joomla.Libraries
 * @subpackage	eshiol.J2XML
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\Tag;
use eshiol\J2xml\Table\User;
use eshiol\J2xml\Table\Viewlevel;
\JLoader::import('eshiol.J2xml.Table.Image');
\JLoader::import('eshiol.J2xml.Table.Table');
\JLoader::import('eshiol.J2xml.Table.Tag');
\JLoader::import('eshiol.J2xml.Table.User');
\JLoader::import('eshiol.J2xml.Table.Viewlevel');

/**
 *
 * Category Table
 *

 * @since 1.5.1
 */
class Category extends Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *			A database connector object
	 *
	 * @since 1.5.1
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		parent::__construct('#__categories', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		$version = new \JVersion();
		if ($version->isCompatible('3.1'))
		{
			// $this->_aliases['tag'] = 'SELECT t.path FROM #__tags t,
			// #__contentitem_tag_map m WHERE type_alias = "' . $this->extension
			// . '.category' . '" AND t.id = m.tag_id AND m.content_item_id = '.
			// $this->id;
			$this->_aliases['tag'] = (string) $this->_db->getQuery(true)
				->select($this->_db->quoteName('t.path'))
				->from($this->_db->quoteName('#__tags', 't'))
				->from($this->_db->quoteName('#__contentitem_tag_map', 'm'))
				->where($this->_db->quoteName('type_alias') . ' = ' . $this->_db->quote($this->extension . '.category'))
				->where($this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('m.tag_id'))
				->where($this->_db->quoteName('m.content_item_id') . ' = ' . $this->_db->quote((string) $this->id));
		}

		$query = $this->_db->getQuery(true);
		$this->_aliases['association'] = (string) $query
			->select('CASE WHEN ' .  $this->_db->quoteName('cc1.level') . ' = 1'
				. ' THEN ' . $this->_db->quoteName('cc1.alias')
				. ' ELSE ' . $query->concatenate(array($this->_db->quoteName('cc2.path'), $this->_db->quoteName('cc1.alias')), '/')
				. ' END')
			->from($this->_db->quoteName('#__associations', 'asso1'))
			->join('INNER', $this->_db->quoteName('#__associations', 'asso2') . ' ON ' . $this->_db->quoteName('asso1.key') . ' = ' . $this->_db->quoteName('asso2.key'))
			->join('INNER', $this->_db->quoteName('#__categories', 'cc1') . ' ON ' . $this->_db->quoteName('asso2.id') . ' = ' . $this->_db->quoteName('cc1.id'))
			->join('INNER', $this->_db->quoteName('#__categories', 'cc2') . ' ON ' . $this->_db->quoteName('cc1.parent_id') . ' = ' . $this->_db->quoteName('cc2.id'))
			->where(array(
				$this->_db->quoteName('asso1.id') . ' = ' . (int) $this->id,
				$this->_db->quoteName('asso1.context') . ' = ' . $this->_db->quote('com_categories.item'),
				$this->_db->quoteName('asso2.id') . ' <> ' . (int) $this->id));

		return parent::toXML($mapKeysToText);
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'fields' 0: No | 1: Yes, if not exists | 2: Yes,
	 *			overwrite if exists
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

		$import_categories = $params->get('categories', 0);
		if ($import_categories == 0)
			return;

		$extension = $params->get('extension');
		if (! $extension)
			return;

		$version = new \JVersion();
		if ($version->isCompatible('3.2'))
		{
			\JFactory::getApplication()->getLanguage()->load('com_users', JPATH_ADMINISTRATOR);
		}
		else 
		{
			\JFactory::getLanguage()->load('com_users', JPATH_ADMINISTRATOR);
		}
		$db = \JFactory::getDbo();
		$version = new \JVersion();

		$keep_id = $params->get('keep_id', 0);
		if ($keep_id)
		{
			$autoincrement = 0;
			$maxid = $db->setQuery($db->getQuery(true)
				->select('MAX(' . $db->quoteName('id') . ')')
				->from($db->quoteName('#__categories')))
				->loadResult();
		}

		foreach ($xml->xpath("//j2xml/category[not(title = '') and (extension = '{$extension}')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$alias = $data['alias']; // =
									 // JApplication::stringURLSafe($data['alias']);
			$id = $data['id'];
			$path = $data['path'];

			$i = strrpos($path, '/');
			if ($i === false)
			{
				$data['parent_id'] = 1;
			}
			else
			{
				$data['parent_id'] = self::getCategoryId(substr($path, 0, $i), $data['extension']);
			}

			if ($data['parent_id'] === false)
			{
				\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title']), \JLog::ERROR, 'lib_j2xml'));
				\JLog::add(new \JLogEntry(\JText::_('JLIB_DATABASE_ERROR_INVALID_PARENT_ID'), \JLog::ERROR, 'lib_j2xml'));
			}
			else
			{
				$query = $db->getQuery(true)
					->select(array(
						$db->quoteName('id'),
						$db->quoteName('title'),
						$db->quoteName('path')
				))
					->from($db->quoteName('#__categories'))
					->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
					->where($db->quoteName('path') . ' = ' . $db->quote($path));
				if ($keep_id)
				{
					$query->where($db->quoteName('id') . ' = ' . $id);
				}
				$db->setQuery($query);
				$category = $db->loadObject();

				$table = new \JTableCategory($db);
				if (! $category || ($import_categories == 2))
				{
					// $table = JTable::getInstance('category');

					if (! $category && ($keep_id == 1))
					{
						$query = $db->getQuery(true)
							->select(array(
								$db->quoteName('id'),
								$db->quoteName('title')
						))
							->from($db->quoteName('#__categories'))
							->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
							->where($db->quoteName('path') . ' = ' . $db->quote($path));
						$db->setQuery($query);
						$category = $db->loadObject();
					}

					if (! $category) // new category
					{
						$data['id'] = null;
						/*
						 * if ($keep_access > 0) $data['access'] = $keep_access;
						 * if ($keep_state < 2) $data['published'] =
						 * $keep_state;
						 * if (!$keep_attribs) $data['params'] =
						 * '{"category_layout":"","image":""}';
						 */
						$table->setLocation($data['parent_id'], 'last-child');
					}
					else // category already exists
					{
						$data['id'] = $category->id;
						$table->load($data['id']);
						/*
						 * if ($keep_access > 0) $data['access'] = null;
						 * if ($keep_state != 0) $data['published'] = null;
						 * if (!$keep_attribs) $data['params'] = null;
						 * if (!$keep_author)
						 * {
						 * $data['created'] = null;
						 * $data['created_user_id'] = null;
						 * $data['created_by_alias'] = null;
						 * $data['modified'] = $now;
						 * $data['modified_user_id'] = $this->_user_id;
						 * $data['version'] = $table->version + 1;
						 * }
						 * else // save default values
						 * {
						 * $data['created'] = $now;
						 * $data['created_user_id'] = $this->_user_id;
						 * $data['created_by_alias'] = null;
						 * $data['modified'] = $this->_nullDate;
						 * $data['modified_user_id'] = null;
						 * $data['version'] = 1;
						 * }
						 */
					}

					$table->bind($data);

					if ($version->isCompatible('3.1') && isset($data['tags']))
					{
						$table->newTags = Tag::convertPathsToIds($data['tags']);
					}

					// Trigger the onContentBeforeSave event.
					// $results = \JFactory::getApplication()->triggerEvent('onContentBeforeSave',
					// array($this->_option.'.category', &$table, $isNew));
					// if (!in_array(false, $results, true))

					if ($table->store())
					{
						if (! $category && ($keep_id == 1) && ($id > 1))
						{
							try
							{
								$query = $db->getQuery(true)
									->update($db->quoteName('#__categories'))
									->set($db->quoteName('id') . ' = ' . $id)
									->where($db->quoteName('id') . ' = ' . $table->id);
								$db->setQuery($query)->execute();
								$table->id = $id;

								$query = $db->getQuery(true)
									->update($db->quoteName('#__assets'))
									->set($db->quoteName('name') . ' = ' . $db->quote($data['extension'] . '.category.' . $id))
									->where($db->quoteName('id') . ' = ' . $table->asset_id);
								$db->setQuery($query)->execute();

								if ($id >= $autoincrement)
								{
									$autoincrement = $id + 1;
								}
							}
							catch (\Exception $ex)
							{
							}
						}
						// Rebuild the tree path.
						$table->rebuildPath();

						self::setAssociations($table->id, $table->language, $data['associations'], 'com_categories.item');

						if ($keep_id && ($id > 0) && ($id != $table->id))
						{
							\JLog::add(
									new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title, $id, $table->id), \JLog::WARNING,
											'lib_j2xml'));
						}
						elseif (empty($data['original_id']))
						{
							\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_CATEGORY_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
						}
						else
						{
							\JLog::add(
									new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title, $data['original_id'], $table->id), \JLog::WARNING,
											'lib_j2xml'));
						}
					}
					else
					{
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title']), \JLog::ERROR, 'lib_j2xml'));
						\JLog::add(new \JLogEntry($table->getError(), \JLog::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
			}
			if ($keep_id && ($autoincrement > $maxid))
			{
				$serverType = $version->isCompatible('3.5') ? $db->getServerType() : 'mysql';

				if ($serverType === 'postgresql')
				{
					$query = 'ALTER SEQUENCE ' . $db->quoteName('#__categories_id_seq') . ' RESTART WITH ' . $autoincrement;
				}
				else
				{
					$query = 'ALTER TABLE ' . $db->quoteName('#__categories') . ' AUTO_INCREMENT = ' . $autoincrement;
				}
				$db->setQuery($query)->execute();
				$maxid = $autoincrement;
			}
		}
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
		
		if ($xml->xpath("//j2xml/category/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Category($db);
		if (! $item->load($id))
		{
			return;
		}

		$allowed_extensions = array(
				'com_content'
		);
		if (in_array($item->extension, $allowed_extensions))
		{
			if (isset($options['content']) && $options['content'])
			{
				$table = '#__' . substr($item->extension, 4);
				$extension = '\\eshiol\\J2xml\\Table\\' . ucfirst(substr($item->extension, 4));
				$query = $db->getQuery(true)
					->select('id')
					->from($table)
					->where('catid = ' . $id);
				$db->setQuery($query);
				$ids_content = $db->loadColumn();
				$options['categories'] = 0;
				foreach ($ids_content as $id_content)
				{
					$extension::export($id_content, $xml, $options);
				}
			}
		}
		$options['content'] = 0;

		if ($item->parent_id > 1)
		{
			Category::export($item->parent_id, $xml, $options);
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

		if ($item->access > 6)
		{
			Viewlevel::export($item->access, $xml, $options);
		}

		if (isset($options['images']) && $options['images'])
		{
			$img = null;
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

			if ($imgs = json_decode($item->params))
			{
				if (isset($imgs->image))
				{
					Image::export($imgs->image, $xml, $options);
				}
			}
		}

		if (isset($options['tags']) && $options['tags'])
		{
			$version = new \JVersion();
			if ($version->isCompatible('3.1'))
			{
				$htags = new \JHelperTags();
				$itemtags = $htags->getItemTags($item->extension . '.category', $id);
				foreach ($itemtags as $itemtag)
				{
					Tag::export($itemtag->tag_id, $xml, $options);
				}
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::prepareData()
	 *
	 * @since 20.5.349
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		$db = \JFactory::getDBO();

		$params->set('extension', 'com_categories');
		parent::prepareData($record, $data, $params);

		if (empty($data['associations']))
		{
			$data['associations'] = array();
		}

		if (isset($data['associationlist']))
		{
			foreach ($data['associationlist']['association'] as $association)
			{
				$id = self::getCategoryId($association, $data['extension']);
				if ($id)
				{
					$tag = $db->setQuery($db->getQuery(true)
						->select($db->quoteName('language'))
						->from($db->quoteName('#__categories'))
						->where($db->quoteName('id') . ' = ' . $id))
						->loadResult();
					if ($tag !== '*')
					{
						$data['associations'][$tag] = $id;
					}
				}
			}
			unset($data['associationlist']);
		}
		elseif (isset($data['association']))
		{
			$id = self::getCategoryId($data['association'], $data['extension']);
			if ($id)
			{
				$tag = $db->setQuery($db->getQuery(true)
					->select($db->quoteName('language'))
					->from($db->quoteName('#__categories'))
					->where($db->quoteName('id') . ' = ' . $id))
					->loadResult();
				if ($tag !== '*')
				{
					$data['associations'][$tag] = $id;
				}
			}
			unset($data['association']);
		}
	}
}
