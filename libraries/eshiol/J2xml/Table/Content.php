<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info@eshiol.it>
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

// use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
\JLoader::import('eshiol.j2xml.Table.Category');
\JLoader::import('eshiol.j2xml.Table.Field');
\JLoader::import('eshiol.j2xml.Table.Image');
\JLoader::import('eshiol.j2xml.Table.Table');
\JLoader::import('eshiol.j2xml.Table.Tag');
\JLoader::import('eshiol.j2xml.Table.User');
\JLoader::import('eshiol.j2xml.Table.Viewlevel');

/**
 * Content table
 *
 * @author Helios Ciancio
 *        
 * @version 19.1.316
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
		if ((new \JVersion())->isCompatible('3.4'))
		{
			// Set the alias since the column is called state
			$this->setColumnAlias('published', 'state');
		}
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
			->select('COALESCE(' . $this->_db->qn('f.ordering') . ', 0)')
			->from($this->_db->qn('#__content_frontpage', 'f'))
			->join('RIGHT', $this->_db->qn('#__content', 'a') . ' ON ' . $this->_db->qn('f.content_id') . ' = ' . $this->_db->qn('a.id'))
			->where($this->_db->qn('a.id') . ' = ' . (int) $this->id);
		\JLog::add(new \JLogEntry($this->_aliases['featured'], \JLog::DEBUG, 'lib_j2xml'));
		
		// $this->_aliases['rating_sum'] = 'SELECT IFNULL(rating_sum,0) FROM
		// #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['rating_sum'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->qn('rating_sum') . ', 0)')
			->from($this->_db->qn('#__content_rating', 'f'))
			->join('RIGHT', $this->_db->qn('#__content', 'a') . ' ON ' . $this->_db->qn('f.content_id') . ' = ' . $this->_db->qn('a.id'))
			->where($this->_db->qn('a.id') . ' = ' . (int) $this->id);
		\JLog::add(new \JLogEntry($this->_aliases['rating_sum'], \JLog::DEBUG, 'lib_j2xml'));
		
		// $this->_aliases['rating_count'] = 'SELECT IFNULL(rating_count,0) FROM
		// #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
		// WHERE a.id = ' . (int)$this->id;
		$this->_aliases['rating_count'] = (string) $this->_db->getQuery(true)
			->select('COALESCE(' . $this->_db->qn('rating_count') . ', 0)')
			->from($this->_db->qn('#__content_rating', 'f'))
			->join('RIGHT', $this->_db->qn('#__content', 'a') . ' ON ' . $this->_db->qn('f.content_id') . ' = ' . $this->_db->qn('a.id'))
			->where($this->_db->qn('a.id') . ' = ' . (int) $this->id);
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
		if ($this->_db->getServerType() == 'sqlserver')
		{
			$this->_aliases['canonical'] = (string) $this->_db->getQuery(true)
				->select($this->_db->q($canonical))
				->from($this->_db->qn('DUAL'));
		}
		else
		{
			$this->_aliases['canonical'] = (string) $this->_db->getQuery(true)->select($this->_db->q($canonical));
		}
		\JLog::add(new \JLogEntry($this->_aliases['canonical'], \JLog::DEBUG, 'lib_j2xml'));
		
		// $this->_aliases['tag']='SELECT t.path FROM #__tags t,
		// #__contentitem_tag_map m WHERE type_alias = "com_content.article" AND
		// t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
		$this->_aliases['tag'] = (string) $this->_db->getQuery(true)
			->select($this->_db->qn('t.path'))
			->from($this->_db->qn('#__tags', 't'))
			->from($this->_db->qn('#__contentitem_tag_map', 'm'))
			->where($this->_db->qn('type_alias') . ' = ' . $this->_db->q('com_content.article'))
			->where($this->_db->qn('t.id') . ' = ' . $this->_db->qn('m.tag_id'))
			->where($this->_db->qn('m.content_item_id') . ' = ' . $this->_db->q((string) $this->id));
		\JLog::add(new \JLogEntry($this->_aliases['tag'], \JLog::DEBUG, 'lib_j2xml'));
		
		if ((new \JVersion())->isCompatible('3.7'))
		{
			// $this->_aliases['field'] = 'SELECT f.name, v.value FROM
			// #__fields_values v, #__fields f WHERE f.id = v.field_id AND
			// v.item_id = '. (int)$this->id;
			$this->_aliases['field'] = (string) $this->_db->getQuery(true)
				->select($this->_db->qn('f.name'))
				->select($this->_db->qn('v.value'))
				->from($this->_db->qn('#__fields_values', 'v'))
				->from($this->_db->qn('#__fields', 'f'))
				->where($this->_db->qn('f.id') . ' = ' . $this->_db->qn('v.field_id'))
				->where($this->_db->qn('v.item_id') . ' = ' . $this->_db->q((string) $this->id));
			\JLog::add(new \JLogEntry($this->_aliases['field'], \JLog::DEBUG, 'lib_j2xml'));
		}
		
		return parent::_serialize();
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param Registry $params
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
	public static function import ($xml, $params)
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
							$db->qn('id'),
							$db->qn('title')
					))
						->from($db->qn('#__content'))
						->where($db->qn('catid') . ' = ' . $db->q($data['catid']))
						->where($db->qn('alias') . ' = ' . $db->q($data['alias'])))
				->loadObject();
			
			$table = new Content($db);
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
				
				if (isset($data['tags']))
				{
					$table->newTags = $data['tags'];
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
						/**
						 * TODO keep featuring
						 * if ($keep_frontpage)
						 * {
						 * if ($data['featured'] == 0)
						 * {
						 * $query = "DELETE FROM #__content_frontpage WHERE
						 * content_id = ".$table->id;
						 * }
						 * else if($keep_id)
						 * {
						 * $query =
						 * ' INSERT IGNORE INTO `#__content_frontpage`'
						 * . ' SET content_id = '.$table->id.','
						 * . ' ordering = '.$data['ordering'];
						 * }
						 * else
						 * {
						 * $frontpage++;
						 * $query =
						 * ' INSERT IGNORE INTO `#__content_frontpage`'
						 * . ' SET content_id = '.$table->id.','
						 * . ' ordering = '.$frontpage;
						 * }
						 * $db->setQuery($query);
						 * $db->query();
						 * }
						 */
						
						/**
						 * if ($keep_rating)
						 * {
						 * if (isset($data['rating_count']))
						 * {
						 * if ($data['rating_count'] > 0)
						 * {
						 * $rating = new stdClass();
						 * $rating->content_id = $table->id;
						 * $rating->rating_count = $data['rating_count'];
						 * $rating->rating_sum = $data['rating_sum'];
						 * $rating->lastip = $_SERVER['REMOTE_ADDR'];
						 * try {
						 * $db->insertObject('#__content_rating', $rating);
						 * } catch (Exception $e) {
						 * $db->updateObject('#__content_rating', $rating,
						 * 'content_id');
						 * }
						 * }
						 * else
						 * {
						 * $query = "DELETE FROM `#__content_rating` WHERE
						 * `content_id`=".$table->id;
						 * $db->setQuery($query);
						 * $db->query();
						 * }
						 * }
						 * }
						 */
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $table->title), \JLog::INFO, 'lib_j2xml'));
						
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
		
		// if (! (new \JVersion())->isCompatible('3.4') && isset($data['published']))
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
		
		$params = new Registry($options);
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
		
		$htags = new \JHelperTags();
		$itemtags = $htags->getItemTags('com_content.article', $id);
		foreach ($itemtags as $itemtag)
		{
			Tag::export($itemtag->tag_id, $xml, $options);
		}
		
		if ((new \JVersion())->isCompatible('3.7'))
		{
			
			$query = $db->getQuery(true)
				->select('DISTINCT field_id')
				->from('#__fields_values')
				->where('item_id = ' . $db->q($id));
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
