<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or die();

use eshiol\J2xml\Table\Category;
use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\User;
\JLoader::import('eshiol.J2xml.Table.Category');
\JLoader::import('eshiol.J2xml.Table.Image');
\JLoader::import('eshiol.J2xml.Table.Table');
\JLoader::import('eshiol.J2xml.Table.User');
\JLoader::register('UsersTableNote', JPATH_ADMINISTRATOR . '/components/com_users/tables/note.php');

/**
 * Usernote Table
 *

 * @since 14.8.240
 */
class Usernote extends \eshiol\J2xml\Table\Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *			A database connector object
	 *
	 * @since 15.3.248
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		parent::__construct('#__user_notes', 'id', $db);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::export()
	 */
	public static function export ($id, &$xml, $options)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

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

		if (isset($options['categories']) && $options['categories'])
		{
			if ($item->catid > 0)
			{
				Category::export($item->catid, $xml, $options);
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::import()
	 */
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$import_usernotes = $params->get('usernotes', 0);
		if ($import_usernotes == 0)
		{
			return;
		}
		
		$params->set('extension', 'com_users');
		$import_categories = $params->get('categories');
		if ($import_categories)
		{
			Category::import($xml, $params);
		}
/*
		$users = json_decode($params->get('imported_users', '[]'), true);
		foreach ($users as $user_id => $overwrite)
		{
			$username = \JFactory::getUser($user_id)->username;
			$path = "//j2xml/usernote[user_id='{$username}']";
*/
			$path = "//j2xml/usernote[user_id!='']";
			foreach ($xml->xpath($path) as $record)
			{
				self::prepareData($record, $data, $params);

				unset($data['id']);

				$table = \JTable::getInstance('Note', 'UsersTable');

//				if (! $overwrite)
//				{
					$table->load(
						array(
							'user_id' => $data['user_id'],
							'catid' => $data['catid'],
							'subject' => $data['subject']
						));
//				}

				$table->bind($data);
				if ($table->store())
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USERNOTE_IMPORTED', $data['subject']), \JLog::INFO, 'lib_j2xml'));
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED', $data['subject'], $table->getError()), \JLog::ERROR, 'lib_j2xml'));
				}
			}
/*
		}
*/
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::prepareData()
	 */
	public static function prepareData ($record, &$data, $params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		$params->set('extension', 'com_users');
		parent::prepareData($record, $data, $params);

		if (isset($data['user_id']))
		{
			$data['user_id'] = self::getUserId($data['user_id']);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::toXML()
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		$this->_aliases['user_id'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('username'))
			->from($this->_db->quoteName('#__users'))
			->where($this->_db->quoteName('id') . ' = ' . (int) $this->user_id);

		return parent::toXML($mapKeysToText);
	}
}
