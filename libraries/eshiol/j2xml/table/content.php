<?php
/** 
 * @version		18.8.308 libraries/eshiol/j2xml/table/content.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.1
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2018 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

class eshTableContent extends eshTable
{
	/**
	* @param database A database connector object
	*/
	function __construct(&$db) {
		parent::__construct('#__content', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML($mapKeysToText = false)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$this->_excluded = array_merge($this->_excluded, array('sectionid','mask','title_alias'));
		$this->_aliases['featured']='SELECT IFNULL(f.ordering,0) FROM #__content_frontpage f RIGHT JOIN #__content a ON f.content_id = a.id WHERE a.id = '. (int)$this->id;
		$this->_aliases['rating_sum']='SELECT IFNULL(rating_sum,0) FROM #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id WHERE a.id = '. (int)$this->id;
		$this->_aliases['rating_count']='SELECT IFNULL(rating_count,0) FROM #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id WHERE a.id = '. (int)$this->id;

		JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
		$slug = $this->alias ? ($this->id . ':' . $this->alias) : $this->id;
		$url = ContentHelperRoute::getArticleRoute($slug, $this->catid, $this->language);
		$config = JFactory::getConfig();
		$router = JRouter::getInstance('site');
		$router->setMode($config->get('sef', 1));
		$this->_aliases['canonical']='SELECT \'' . str_replace(JUri::base(true) . '/', JUri::root(), $router->build($url)) . '\' FROM DUAL';		

		$this->_aliases['tag']='SELECT t.path FROM #__tags t, #__contentitem_tag_map m WHERE type_alias = "com_content.article" AND t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
		$this->_aliases['field'] = 'SELECT f.name, v.value FROM #__fields_values v, #__fields f WHERE f.id = v.field_id AND v.item_id = '. (int)$this->id;

		return parent::_serialize();
	}
}
