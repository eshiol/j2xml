<?php
/** 
 * @version		15.9.266 libraries/eshiol/j2xml/table/content.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.1
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2015 Helios Ciancio. All Rights Reserved
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
		$this->_excluded = array_merge($this->_excluded, array('sectionid','mask','title_alias'));
		$this->_aliases['featured']='SELECT IFNULL(f.ordering,0) FROM #__content_frontpage f RIGHT JOIN #__content a ON f.content_id = a.id WHERE a.id = '. (int)$this->id;
		$this->_aliases['rating_sum']='SELECT IFNULL(rating_sum,0) FROM #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id WHERE a.id = '. (int)$this->id;
		$this->_aliases['rating_count']='SELECT IFNULL(rating_count,0) FROM #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id WHERE a.id = '. (int)$this->id;
/*
		$this->_aliases['source']='
				SELECT CONCAT(\'index.php?option=com_content&view=article&id=\',a.id,\':\',a.alias,\'&catid=\',a.catid,\':\',c.alias,
				IF(m.id, CONCAT(\'&Itemid=\',m.id), \'\'))
				FROM #__content a 
				INNER JOIN #__categories c ON a.catid = c.id 
				LEFT JOIN #__menu m ON m.link = CONCAT(\'index.php?option=com_content&view=article&id=\',a.id)
				WHERE a.id = '.(int)$this->id.'
				UNION
				SELECT CONCAT(\'index.php?option=com_content&view=article&id=\',a.id,\':\',a.alias,\'&catid=\',a.catid,\':\',c.alias,\'\')
				FROM #__content a 
				INNER JOIN #__categories c ON a.catid = c.id 
				LEFT JOIN #__menu m ON m.link = CONCAT(\'index.php?option=com_content&view=article&id=\',a.id)
				WHERE a.id = '.(int)$this->id;
*/					
		if (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge'))
			$this->_aliases['tag']='SELECT t.path FROM #__tags t, #__contentitem_tag_map m WHERE type_alias = "com_content.article" AND t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
		
		return parent::_serialize();
	}
}
