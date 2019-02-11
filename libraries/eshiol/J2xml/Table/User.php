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

use eshiol\J2XML\Table\Contact;
use eshiol\J2XML\Table\Field;
use eshiol\J2XML\Table\Table;
use eshiol\J2XML\Table\Usernote;
\JLoader::import('eshiol.j2xml.Table.Contact');
\JLoader::import('eshiol.j2xml.Table.Field');
\JLoader::import('eshiol.j2xml.Table.Table');
\JLoader::import('eshiol.j2xml.Table.Usernote');
\JLoader::register('UsersModelUser', JPATH_ADMINISTRATOR . '/components/com_users/models/user.php');

/**
 * User Table
 *
 * @version 19.2.322
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
		
		$serverType = (new \JVersion())->isCompatible('3.5') ? $this->_db->getServerType() : 'mysql';
		
		if ($serverType === 'postgresql')
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
				->select('usergroups_getpath(' . $this->_db->quoteName('id') . ')')
				->from($this->_db->quoteName('#__usergroups', 'g'))
				->from($this->_db->quoteName('#__user_usergroup_map', 'm'))
				->where($this->_db->quoteName('g.id') . ' = ' . $this->_db->quoteName('m.group_id'))
				->where($this->_db->quoteName('m.user_id') . ' = ' . (int) $this->id);
		}
		\JLog::add(new \JLogEntry($this->_aliases['group'], \JLog::DEBUG, 'lib_j2xml'));
		
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
		
		// $this->_aliases['profile'] = 'SELECT profile_key name, profile_value
		// value FROM #__user_profiles WHERE user_id = '. (int)$this->id;
		$this->_aliases['profile'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('profile_key', 'name'))
			->select($this->_db->quoteName('profile_value', 'value'))
			->from($this->_db->quoteName('#__user_profiles'))
			->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($this->id));
		\JLog::add(new \JLogEntry($this->_aliases['profile'], \JLog::DEBUG, 'lib_j2xml'));
		
		return parent::_serialize();
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *        	xml
	 * @param \JRegistry $params
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
	public static function import ($xml, &$params)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		
		$import_users = $params->get('users', 1);
		$import_superusers = $params->get('superusers', 0);
		if (! $import_users)
			return;
		
		$keepId = $params->get('keep_user_id', '0');
		$keep_user_attribs = $params->get('keep_user_attribs', '1');
		
		\JFactory::getLanguage()->load('com_users', JPATH_ADMINISTRATOR);
		
		$db = \JFactory::getDbo();
		
		$autoincrement = 0;
		$maxid = $db->setQuery($db->getQuery(true)
			->select('MAX(' . $db->quoteName('id') . ')')
			->from($db->quoteName('#__users')))
			->loadResult();
		
		$users = array();
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
				foreach ($data['grouplist']['group'] as $v)
				{
					$data['groups'][] = parent::getUsergroupId($v);
				}
				unset($data['grouplist']);
			}
			
			if (! $import_superusers && isset($data['groups']) && in_array(8, $data['groups']))
			{
				\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USER_SKIPPED', $data['name']), \JLog::NOTICE, 'lib_j2xml'));
				continue;
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
			
			$userId = $data['id'];
			unset($data['id']);
			
			$data['id'] = $db->setQuery(
					$db->getQuery(true)
						->select($db->quoteName('id'))
						->from($db->quoteName('#__users'))
						->where($db->quoteName('username') . ' = ' . $db->quote($data['username'])))
				->loadResult();
			
			if (! $data['id'] || ($import_users == 2))
			{
				\JLog::add(new \JLogEntry(print_r($data, true), \JLog::DEBUG, 'lib_j2xml'));
				
				$user = new \UsersModelUser();
				$result = $user->save($data);
				
				$id = $db->setQuery(
						$db->getQuery(true)
							->select($db->quoteName('id'))
							->from($db->quoteName('#__users'))
							->where($db->quoteName('username') . ' = ' . $db->quote($data['username'])))
					->loadResult();
				
				if ($id)
				{
					$users[$id] = ! (bool) $data['id'];
					
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
						$query = $db->getQuery(true)
							->update('#__users')
							->set($db->quoteName('password') . ' = ' . $db->quote($data['password_crypted']))
							->where($db->quoteName('id') . ' = ' . $id);
						\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
						$db->setQuery($query)->execute();
					}
					
					if (($userId != $id) && ($keepId == 1))
					{
						$id = $user->getState('user.id');
						$query = $db->getQuery(true)
							->update('#__users')
							->set($db->quoteName('id') . ' = ' . $userId)
							->where($db->quoteName('id') . ' = ' . $id);
						\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
						$db->setQuery($query)->execute();
						
						$query = $db->getQuery(true)
							->update('#__user_usergroup_map')
							->set($db->quoteName('user_id') . ' = ' . $userId)
							->where($db->quoteName('user_id') . ' = ' . $id);
						\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
						$db->setQuery($query)->execute();
						
						if ($userId >= $autoincrement)
						{
							$autoincrement = $userId + 1;
						}
						
						$id = $userId;
					}
					
					try
					{
						$query = $db->getQuery(true)
							->delete($db->quoteName('#__user_profiles'))
							->where($db->quoteName('user_id') . ' = ' . $id);
						\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
						$db->setQuery($query)->execute();
						
						if (isset($data['profile']))
						{
							$query = $db->getQuery(true)->insert($db->quoteName('#__user_profiles'));
							$query->values($id . ', ' . $db->quote($data['profile']['name']) . ', ' . $db->quote($data['profile']['value']) . ', 1');
							\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
							$db->setQuery($query)->execute();
						}
						elseif (isset($data['profilelist']))
						{
							\JLog::add(new \JLogEntry(print_r($data['profilelist'], true), \JLog::DEBUG, 'lib_j2xml'));
							$query = $db->getQuery(true)->insert($db->quoteName('#__user_profiles'));
							$order = 1;
							$query->columns(
									$db->quoteName(
											array(
													'user_id',
													'profile_key',
													'profile_value',
													'ordering'
											)));
							foreach ($data['profilelist']['profile'] as $v)
							{
								$query->values($id . ', ' . $db->quote($v['name']) . ', ' . $db->quote($v['value']) . ', ' . $order ++);
							}
							\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
							$db->setQuery($query)->execute();
						}
					}
					catch (\JException $e)
					{
						\JLog::add(new \JLogEntry(\JText::sprintf('LIB_J2XML_MSG_USER_NO_PROFILE', $data['name']), \JLog::WARNING, 'lib_j2xml'));
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
		
		$serverType = (new \JVersion())->isCompatible('3.5') ? $db->getServerType() : 'mysql';
		if ($autoincrement > $maxid)
		{
			if ($serverType === 'postgresql')
			{
				$query = 'ALTER SEQUENCE ' . $db->quoteName('#__users_id_seq') . ' RESTART WITH ' . $autoincrement;
			}
			else
			{
				$query = 'ALTER TABLE ' . $db->quoteName('#__users') . ' AUTO_INCREMENT = ' . $autoincrement;
			}
			\JLog::add(new \JLogEntry($query, \JLog::DEBUG, 'lib_j2xml'));
			$db->setQuery($query)->execute();
			$maxid = $autoincrement;
		}
		
		$params->set('imported_users', json_encode($users));
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
				->where('item_id = ' . $db->quote($id));
			$db->setQuery($query);
			
			$ids_field = $db->loadColumn();
			foreach ($ids_field as $id_field)
			{
				Field::export($id_field, $xml, $options);
			}
		}
	}
}
