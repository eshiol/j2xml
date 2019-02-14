<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2XML\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2XML\Table\Category;
use eshiol\J2XML\Table\Field;
use eshiol\J2XML\Table\Image;
use eshiol\J2XML\Table\Table;
use eshiol\J2XML\Table\Tag;
use eshiol\J2XML\Table\User;
use eshiol\J2XML\Table\Viewlevel;
\JLoader::import('eshiol.j2xml.Table.Category');
\JLoader::import('eshiol.j2xml.Table.Field');
\JLoader::import('eshiol.j2xml.Table.Image');
\JLoader::import('eshiol.j2xml.Table.Table');
\JLoader::import('eshiol.j2xml.Table.Tag');
\JLoader::import('eshiol.j2xml.Table.User');
\JLoader::import('eshiol.j2xml.Table.Viewlevel');

jimport('joomla.application.router');

/**
 * Content table
 *
 * @version 19.2.325
 * @since 1.5.1
 */
class Content extends Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *        	A database connector object
	 *        
	 * @since 1.5.1
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		parent::__construct('#__content', 'id', $db);

		/*
		 * if ((new \JVersion())->isCompatible('3.4'))
		 * {
		 * // Set the alias since the column is called state
		 * $this->setColumnAlias('published', 'state');
		 * }
		 */
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$this->_excluded = array_merge($this->_excluded, array(
				'sectionid',
				'mask',
				'title_alias'
		));

		// $this->_aliases['featured'] = 'SELECT IFNULL(f.ordering,0) FROM
		// #__content_frontpage f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['featured'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->quoteName('f.ordering') . ', 0)')
			->from($this->_db->quoteName('#__content_frontpage', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);
		\JLog::add(new \JLogEntry($this->_aliases['featured'], \JLog::DEBUG, 'lib_j2xml'));

		// $this->_aliases['rating_sum'] = 'SELECT IFNULL(rating_sum,0) FROM
		// #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['rating_sum'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->quoteName('rating_sum') . ', 0)')
			->from($this->_db->quoteName('#__content_rating', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);
		\JLog::add(new \JLogEntry($this->_aliases['rating_sum'], \JLog::DEBUG, 'lib_j2xml'));

		// $this->_aliases['rating_count'] = 'SELECT IFNULL(rating_count,0) FROM
		// #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['rating_count'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->quoteName('rating_count') . ', 0)')
			->from($this->_db->quoteName('#__content_rating', 'f'))
			->join('RIGHT',
				$this->_db->quoteName('#__content', 'a') . ' ON ' . $this->_db->quoteName('f.content_id') . ' = ' . $this->_db->quoteName('a.id'))
			->where($this->_db->quoteName('a.id') . ' = ' . (int) $this->id);
		\JLog::add(new \JLogEntry($this->_aliases['rating_count'], \JLog::DEBUG, 'lib_j2xml'));

		\JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
		$config = \JFactory::getConfig();
		$router = \JRouter::getInstance('site');
		$router->setMode($config->get('sef', 1));
		$slug = $this->alias ? ($this->id . ':' . $this->alias) : $this->id;
		$url = \ContentHelperRoute::getArticleRoute($slug, $this->catid, $this->language);
		$canonical = str_replace(\JUri::base(true) . '/', \JUri::root(), $router->build($url));
		// $this->_aliases['canonical'] = 'SELECT \'' . $canonical . '\' FROM
		// DUAL';
		$serverType = (new \JVersion())->isCompatible('3.5') ? $this->_db->getServerType() : 'mysql';
		if ($serverType === 'sqlserver')
		{
			$this->_aliases['canonical'] = (string) $this->_db->getQuery(true)
				->select($this->_db->quote($canonical))
				->from($this->_db->quoteName('DUAL'));
		}
		else
		{
			$this->_aliases['canonical'] = (string) $this->_db->getQuery(true)->select($this->_db->quote($canonical));
		}
		\JLog::add(new \JLogEntry($this->_aliases['canonical'], \JLog::DEBUG, 'lib_j2xml'));

		if ((new \JVersion())->isCompatible('3.1'))
		{
			// $this->_aliases['tag']='SELECT t.path FROM #__tags t,
			// #__contentitem_tag_map m WHERE type_alias = "com_content.article"
			// AND
			// t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
			$this->_aliases['tag'] = (string) $this->_db->getQuery(true)
				->select($this->_db->quoteName('t.path'))
				->from($this->_db->quoteName('#__tags', 't'))
				->from($this->_db->quoteName('#__contentitem_tag_map', 'm'))
				->where($this->_db->quoteName('type_alias') . ' = ' . $this->_db->quote('com_content.article'))
				->where($this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('m.tag_id'))
				->where($this->_db->quoteName('m.content_item_id') . ' = ' . $this->_db->quote((string) $this->id));
			\JLog::add(new \JLogEntry($this->_aliases['tag'], \JLog::DEBUG, 'lib_j2xml'));
		}

		if ((new \JVersion())->isCompatible('3.7'))
		{
			// $this->_aliases['field'] = 'SELECT f.name, v.value FROM
			// #__fields_values v, #__fields f WHERE f.id = v.field_id AND
			// v.item_id = '. (int)$this->id;
			$this->_aliases['field'] = (string) $this->_db->getQuery(true)
				->select($this->_db->quoteName('f.name'))
				->select($this->_db->quoteName('v.value'))
				->from($this->_db->quoteName('#__fields_values', 'v'))
				->from($this->_db->quoteName('#__fields', 'f'))
				->where($this->_db->quoteName('f.id') . ' = ' . $this->_db->quoteName('v.field_id'))
				->where($this->_db->quoteName('v.item_id') . ' = ' . $this->_db->quote((string) $this->id));
			\JLog::add(new \JLogEntry($this->_aliases['field'], \JLog::DEBUG, 'lib_j2xml'));
		}

		return parent::_serialize();
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param \JRegistry $params
	 *        	@option int 'content' 0: No (default); 1: Yes, if not exists;
	 *        	2: Yes, overwrite if exists
	 *        	@option int 'content_category_default'
	 *        	@option int 'content_category_forceto'
	 *        	@option string 'context'
	 *        
	 * @throws
	 * @return void
	 * @access public
	 *        
	 * @since 18.8.301
	 */
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		$import_content = $params->get('content', 0);
		if ($import_content == 0)
			return;

		$params->def('content_category_default', self::getCategoryId('uncategorised', 'com_content'));
		$force_to = $params->get('content_category_forceto');
		$context = $params->get('context', 'com_content.article');
		$db = \JFactory::getDBO();
		$nullDate = $db->getNullDate();
		$userid = \JFactory::getUser()->id;

		\JPluginHelper::importPlugin('content');

		$params->set('extension', 'com_content');
		$import_categories = $params->get('categories');
		if ($import_categories)
		{
			Category::import($xml, $params);
		}

		$keep_id = $params->get('keep_id', 0);
		if ($keep_id)
		{
			$autoincrement = 0;
			$maxid = $db->setQuery($db->getQuery(true)
				->select('MAX(' . $db->quoteName('id') . ')')
				->from($db->quoteName('#__content')))
				->loadResult();
		}

		foreach ($xml->xpath("//j2xml/content[not(name = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$id = $data['id'];
			if ($force_to)
			{
				$data['catid'] = $force_to;
			}

			$content = $db->setQuery(
					$db->getQuery(true)
						->select(array(
							$db->quoteName('id'),
							$db->quoteName('title')
					))
						->from($db->quoteName('#__content'))
						->where($db->quoteName('catid') . ' = ' . $db->quote($data['catid']))
						->where($db->quoteName('alias') . ' = ' . $db->quote($data['alias'])))
				->loadObject();

			$table = new \JTableContent($db);
			if (! $content || ($import_content == 2))
			{
				if (! $content)
				{ // new article
					$isNew = true;
					$data['id'] = null;
				}
				else
				{ // article already exists
					$isNew = false;
					$data['id'] = $content->id;
				}

				\JLog::add(new \JLogEntry(print_r($data, true), \JLog::DEBUG, 'lib_j2xml'));
				$table->bind($data);

				if ((new \JVersion())->isCompatible('3.1'))
				{
					if (isset($data['tags']))
					{
						$table->newTags = $data['tags'];
					}
				}

				// Trigger the onContentBeforeSave event.
				$result = \JFactory::getApplication()->triggerEvent('onContentBeforeSave',
						array(
								$params->get('context'),
								&$table,
								$isNew
						));

				if (! in_array(false, $result, true))
				{
					if ($table->store())
					{
						if (($keep_id == 1) && ($id > 1))
						{
							try
							{
								$query = $db->getQuery(true)
									->update($db->quoteName('#__content'))
									->set($db->quoteName('id') . ' = ' . $id)
									->where($db->quoteName('id') . ' = ' . $table->id);
								\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
								$db->setQuery($query)->execute();
								$table->id = $id;

								$query = $db->getQuery(true)
									->update($db->quoteName('#__assets'))
									->set($db->quoteName('name') . ' = ' . $db->quote('com_content.article.' . $id))
									->where($db->quoteName('id') . ' = ' . $table->asset_id);
								\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
								$db->setQuery($query)->execute();

								if ($id >= $autoincrement)
								{
									$autoincrement = $id + 1;
								}

								\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
							}
							catch (\Exception $ex)
							{
								\JLog::add(
										new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_ARTICLE_ID_PRESENT', $table->title, $id, $table->id),
												\JLog::WARNING, 'lib_j2xml'));
							}

							// TODO: featuring, rating
						}
						else
						{
							\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
						}

						// Trigger the onContentAfterSave event.
						$result = \JFactory::getApplication()->triggerEvent('onContentAfterSave',
								array(
										$params->get('context'),
										&$table,
										$isNew
								));
					}
					else
					{
						\JLog::add(
								new \JLogEntry(
										\JText::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'] . ' (id = ' . $id . ')',
												$table->getError()), \JLog::ERROR, 'lib_j2xml'));
					}
				}
			}
			if ($keep_id && ($autoincrement > $maxid))
			{
				$serverType = (new \JVersion())->isCompatible('3.5') ? $db->getServerType() : 'mysql';

				if ($serverType === 'postgresql')
				{
					$query = 'ALTER SEQUENCE ' . $db->quoteName('#__content_id_seq') . ' RESTART WITH ' . $autoincrement;
				}
				else
				{
					$query = 'ALTER TABLE ' . $db->quoteName('#__content') . ' AUTO_INCREMENT = ' . $autoincrement;
				}
				\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
				$db->setQuery($query)->execute();
				$maxid = $autoincrement;
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::prepareData()
	 *
	 * @since 18.8.301
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));

		parent::prepareData($record, $data, $params);

		if (empty($data['alias']))
		{
			$data['alias'] = $data['title'];
			$data['alias'] = str_replace(' ', '-', $data['alias']);
		}

		if (! isset($data['fulltext']))
		{
			$data['fulltext'] = '';
		}
		if (! isset($data['metakey']))
		{
			$data['metakey'] = '';
		}
		if (! isset($data['metadesc']))
		{
			$data['metadesc'] = '';
		}
		if (! isset($data['created_by']))
		{
			$data['created_by'] = \JFactory::getUser()->id;
		}
		if (! isset($data['language']))
		{
			$data['language'] = '*';
		}

		// if (! (new \JVersion())->isCompatible('3.4') &&
		// isset($data['published']))
		if (isset($data['published']))
		{
			// Set the column since its name is changed from published to state
			$data['state'] = $data['published'];
			unset($data['published']);
		}
	}

	/**
	 * Export data
	 *
	 * @param int $id
	 *        	the id of the item to be exported
	 * @param \SimpleXMLElement $xml
	 *        	xml
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
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('id: ' . $id, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('options: ' . print_r($options, true), \JLog::DEBUG, 'lib_j2xml'));

		if ($xml->xpath("//j2xml/content/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Content($db);
		if (! $item->load($id))
		{
			return;
		}

		$params = new \JRegistry($options);
		$dispatcher = \JEventDispatcher::getInstance();
		\JPluginHelper::importPlugin('j2xml');
		$results = $dispatcher->trigger('onBeforeContentExport', array(
				'lib_j2xml.article',
				&$item,
				$params
		));

		if ($item->access > 6)
		{
			Viewlevel::export($item->access, $xml, $options);
		}

		if ($options['categories'] && ($item->catid > 0))
		{
			Category::export($item->catid, $xml, $options);
		}

		if ((new \JVersion())->isCompatible('3.1'))
		{
			$htags = new \JHelperTags();
			$itemtags = $htags->getItemTags('com_content.article', $id);
			foreach ($itemtags as $itemtag)
			{
				Tag::export($itemtag->tag_id, $xml, $options);
			}
		}

		if ((new \JVersion())->isCompatible('3.7'))
		{

			$query = $db->getQuery(true)
				->select('DISTINCT field_id')
				->from('#__fields_values')
				->where('item_id = ' . $db->quote($id));
			$db->setQuery($query);

			$ids_field = $db->loadColumn();
			foreach ($ids_field as $id_field)
			{
				Field::export($id_field, $xml, $options);
			}
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['users'])
		{
			if ($item->created_by)
			{
				User::export($item->created_by, $xml, $options);
			}
			if ($item->modified_by)
			{
				User::export($item->modified_by, $xml, $options);
			}
		}

		if ($options['images'])
		{
			$img = null;
			$text = $item->introtext . $item->fulltext;
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
}
