<?php
/**
 * @version		17.1.294 libraries/eshiol/j2xml/table/menu.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		17.1.294
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
defined('_JEXEC') or die('Restricted access');

/**
* Menu Table class
* 
* @since 17.1.294
*/
class eshTableMenu extends eshTable
{
	/**
	 * Constructor
	 * 
	 * @param object $db	Database connector
	 * 
	 * @since 17.1.294
	 */
	function __construct(&$db) {
		parent::__construct('#__menu', 'id', $db);
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see eshTable::toXML()
	 */
	function toXML($mapKeysToText = false)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

		if ($this->type == 'component')
		{
			$this->_aliases['component_id'] =
				'SELECT '.$this->_db->qn('name')
				.' FROM '.$this->_db->qn('#__extensions')
				.' WHERE '.$this->_db->qn('extension_id').' = '.(int)$this->component_id;

			$args = array();
			parse_str(parse_url($this->link, PHP_URL_QUERY), $args);
			if (isset($args['option']) && ($args['option'] == 'com_content'))
			{
				if (isset($args['view']) && ($args['view'] == 'article'))
				{
					$this->_aliases['article_id'] = 
						'SELECT CONCAT('.$this->_db->qn('c.path').', '.$this->_db->q('/').', '.$this->_db->qn('a.alias').')'
						.' FROM '.$this->_db->qn('#__content').' a'
						.' INNER JOIN '.$this->_db->qn('#__categories').' c'
						.' ON '.$this->_db->qn('a.catid').' = '.$this->_db->qn('c.id')
						.' WHERE '.$this->_db->qn('a.id').' = '.(int)$args['id']
						;
				}
				JLog::add(new JLogEntry('article_id: '.$this->_aliases['article_id'], JLOG::DEBUG, 'lib_j2xml'));
			}

		}
		return parent::_serialize();
	}
}
