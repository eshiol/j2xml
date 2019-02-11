<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
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
use eshiol\J2XML\Table\Image;
use eshiol\J2XML\Table\Table;
use eshiol\J2XML\Table\User;
\JLoader::import('eshiol.j2xml.Table.Category');
\JLoader::import('eshiol.j2xml.Table.Image');
\JLoader::import('eshiol.j2xml.Table.Table');
\JLoader::import('eshiol.j2xml.Table.User');

/**
 * Usernote Table
 *
 * @version 19.2.319
 * @since 14.8.240
 */
class Usernote extends \eshiol\J2XML\Table\Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *        	A database connector object
	 *        
	 * @since 15.3.248
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		parent::__construct('#__user_notes', 'id', $db);
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

		if ($xml->xpath("//j2xml/usernote/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \JFactory::getDbo();
		$item = new Usernote($db);
		if (! $item->load($id))
		{
			return;
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		if ($options['users'])
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

		if ($options['images'])
		{
			$img = null;
			$text = html_entity_decode($item->body);
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
		}

		if ($options['categories'] && ($item->catid > 0))
		{
			Category::export($item->catid, $xml, $options);
		}
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param \JRegistry $params
	 *        	@option int 'usernote' 0: No | 1: Yes, if not exists | 2: Yes,
	 *        	overwrite if exists
	 *        	@option string 'context'
	 *        
	 * @throws
	 * @return void
	 * @access public
	 *        
	 * @since 18.8.310
	 */
	public static function import ($xml, $params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
	}
}
