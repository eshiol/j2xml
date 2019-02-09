<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info@eshiol.it>
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

use eshiol\J2XML\Table\Contact;
use eshiol\J2XML\Table\Field;
use eshiol\J2XML\Table\Table;
use eshiol\J2XML\Table\Usernote;

// use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
\JLoader::import('eshiol.j2xml.Table.Contact');
\JLoader::import('eshiol.j2xml.Table.Field');
\JLoader::import('eshiol.j2xml.Table.Table');
\JLoader::import('eshiol.j2xml.Table.Usernote');
\JLoader::register('UsersModelUser', JPATH_ADMINISTRATOR . '/components/com_users/models/user.php');

/**
 * User Table
 *
 * @author Helios Ciancio
 *        
 * @version 18.11.314
 * @since 1.5.3beta4.39
 */
class User extends Table
{

	/**
	 * Constructor
	 *
	 * @param \JDatabaseDriver $db
	 *        	A database connector object
	 *        	
	 * @since 1.5.3beta4.39
	 */
	public function __construct (\JDatabaseDriver $db)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));
		
		parent::__construct('#__users', 'id', $db);
	}

	/**
	 * Export item list to xml
	 *
	 * @access public
	 */
	function toXML ($mapKeysToText = false)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		
		if ($this->_db->getServerType() == 'postgresql')
		{
			$this->_aliases['group'] = '
				WITH RECURSIVE usergroups(id, title, parent_id, depth, path) AS (
				  SELECT tn.id, tn.title, tn.parent_id, 1::INT AS depth, tn.title::TEXT AS path 
				  FROM #__usergroups AS tn 
				  WHERE tn.parent_id = 0
				UNION ALL
				  SELECT c.id, c.title, c.parent_id, p.depth + 1 AS depth, 
				        (p.path || \'","\' || c.title) AS path
				  FROM usergroups AS p, #__usergroups AS c 
				  WHERE c.parent_id = p.id
				)
				SELECT (\'["\' || path || \'"]\')
				FROM usergroups g INNER JOIN #__user_usergroup_map m ON g.id = m.group_id
				WHERE m.user_id = ' . (int) $this->id;
		}
		else
		{
			$this->_aliases['group'] = (string) $this->_db->getQuery(true)
				->select('usergroups_getpath(' . $this->_db->qn('id') . ')')
				->from($this->_db->qn('#__usergroups', 'g'))
				->from($this->_db->qn('#__user_usergroup_map', 'm'))
				->where($this->_db->qn('g.id') . ' = ' . $this->_db->qn('m.group_id'))
				->where($this->_db->qn('m.user_id') . ' = ' . (int) $this->id);
		}
		\JLog::add(new \JLogEntry($this->_aliases['group'], \JLog::DEBUG, 'lib_j2xml'));
		
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
	 *        	@option int 'tags' 1: Yes, if not exists; 2: Yes, overwrite if
	 *        	exists
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
		
		$import_users = $params->get('users', 1);
		if (! $import_users)
			return;
		
		$keep_user_id = $params->get('keep_user_id', '0');
		$keep_user_attribs = $params->get('keep_user_attribs', '1');
		
		\JFactory::getLanguage()->load('com_users', JPATH_ADMINISTRATOR);
		
		$db = \JFactory::getDbo();
		$autoincrement = 0;
		
		$maxid = $db->setQuery($db->getQuery(true)
			->select('MAX(' . $db->qn('id') . ')')
			->from($db->qn('#__users')))
			->loadResult();
		
		foreach ($xml->xpath("//j2xml/user[not(username = '')]") as $record)
		{
			self::prepareData($record, $data, $params);
			
			if (isset($data['group']))
			{
				$data['groups'][] = parent::getUsergroupId($data['group']);
				unset($data['group']);
			}
			elseif (isset($data['grouplist']))
			{
				$data['groups'] = array();
				foreach ($data['grouplist'] as $v)
				{
					$data['groups'][] = parent::getUsergroupId($v);
				}
				unset($data['grouplist']);
			}
			
			if (isset($data['password']))
			{
				$data['password_crypted'] = $data['password'];
				$data['password2'] = $data['password'] = \JText::_('LIB_J2XML_PASSWORD_NOT_AVAILABLE');
			}
			elseif (isset($data['password_clear']))
			{
				$data['password'] = $data['password2'] = $data['password_clear'];
			}
			else
			{
				$data['password'] = $data['password2'] = JUserHelper::genRandomPassword();
			}
			
			$user_id = $data['id'];
			unset($data['id']);
			
			$data['id'] = $db->setQuery(
					$db->getQuery(true)
						->select($db->qn('id'))
						->from($db->qn('#__users'))
						->where($db->qn('username') . ' = ' . $db->q($data['username'])))
				->loadResult();
			
			if (! $data['id'] || ($import_users == 2))
			{
				\JLog::add(new \JLogEntry(print_r($data, true), \JLog::DEBUG, 'lib_j2xml'));
				
				$user = new \UsersModelUser();
				$result = $user->save($data);
				
				$id = $db->setQuery(
						$db->getQuery(true)
							->select($db->qn('id'))
							->from($db->qn('#__users'))
							->where($db->qn('username') . ' = ' . $db->q($data['username'])))
					->loadResult();
				if ($id)
				{
					if ($error = $user->getError())
					{
						\JLog::add(
								new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USER_IMPORTED_WITH_ERRORS', $data['name']), \JLog::WARNING, 'lib_j2xml'));
						\JLog::add(new \JLogEntry($error, \JLog::WARNING, 'lib_j2xml'));
					}
					else
					{
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USER_IMPORTED', $data['name']), \JLog::INFO, 'lib_j2xml'));
					}
					if (isset($data['password_crypted']))
					{
						// set password
						$db->setQuery(
								$db->getQuery(true)
									->update('#__users')
									->set($db->qn('password') . ' = ' . $db->q($data['password_crypted']))
									->where($db->qn('id') . ' = ' . $id))
							->execute();
						
						if (($user_id != $data['id']) && ($keep_user_id == 1))
						{
							$id = $user->getState('user.id');
							$db->setQuery(
									$db->getQuery(true)
										->update('#__users')
										->set($db->qn('id') . ' = ' . $user_id)
										->where($db->qn('id') . ' = ' . $data['id']))
								->execute();
							$db->setQuery(
									$db->getQuery(true)
										->update('#__user_usergroup_map')
										->set($db->qn('user_id') . ' = ' . $user_id)
										->where($db->qn('user_id') . ' = ' . $data['id']))
								->execute();
							if ($user_id >= $autoincrement)
							{
								$autoincrement = $user_id + 1;
							}
						}
					}
				}
				else
				{
					\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USER_NOT_IMPORTED', $data['name']), \JLog::ERROR, 'lib_j2xml'));
					if ($error = $user->getError())
					{
						\JLog::add(new \JLogEntry($error, \JLog::WARNING, 'lib_j2xml'));
					}
				}
			}
		}
		
		if ($autoincrement > $maxid)
		{
			if ($db->getServerType() == 'postgresql')
			{
				$query = 'ALTER SEQUENCE ' . $db->qn('#__users_id_seq') . ' RESTART WITH ' . $autoincrement;
			}
			else
			{
				$query = 'ALTER TABLE ' . $db->qn('#__users') . ' AUTO_INCREMENT = ' . $autoincrement;
			}
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$db->setQuery($query)->execute();
			$maxid = $autoincrement;
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
		
		$db = \JFactory::getDbo();
		
		// fix null date
		if (($data['lastResetTime'] == '0000-00-00 00:00:00') || ($data['lastResetTime'] == '1970-01-01 00:00:00'))
		{
			$data['lastResetTime'] = $db->getNullDate();
		}
		
		// fix null date
		if (($data['lastvisitDate'] == '0000-00-00 00:00:00') || ($data['lastvisitDate'] == '1970-01-01 00:00:00'))
		{
			$data['lastvisitDate'] = $db->getNullDate();
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
		
		if ($xml->xpath("//j2xml/user/id[text() = '" . $id . "']"))
		{
			return;
		}
		
		$db = \JFactory::getDbo();
		$item = new User($db);
		if (! $item->load($id))
		{
			return;
		}
		
		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();
		
		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);
		
		$db = \JFactory::getDbo();
		
		if ($options['contacts'])
		{
			$query = $db->getQuery(true)
				->select('id')
				->from('#__contact_details')
				->where('user_id = ' . $id);
			$db->setQuery($query);
			
			$ids_contact = $db->loadColumn();
			foreach ($ids_contact as $id_contact)
			{
				Contact::export($id_contact, $xml, $options);
			}
		}
		
		$query = $db->getQuery(true)
			->select('id')
			->from('#__user_notes')
			->where('user_id = ' . $id);
		$db->setQuery($query);
		
		$ids_usernote = $db->loadColumn();
		foreach ($ids_usernote as $id_usernote)
		{
			Usernote::export($id_usernote, $xml, $options);
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
	}
}
