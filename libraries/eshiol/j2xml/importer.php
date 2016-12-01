<?php
/**
 * @version		16.11.287 libraries/eshiol/j2xml/importer.php
 * 
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.6.0
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

use Joomla\Registry\Registry;

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_weblinks/tables');
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_contact/tables');
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_buttons/tables');

//jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.user.helper');

if (class_exists('JHelperTags'))
	jimport('eshiol.j2xml.helper.tags');

class J2XMLImporter
{
	private $_nullDate;
	private $_user_id;
	private $_now;
	private $_option;
	private $_usergroups;
	
	function __construct()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		// Merge the default translation with the current translation
		$jlang = JFactory::getLanguage();
		$jlang->load('lib_j2xml', JPATH_SITE, 'en-GB', true);
		$jlang->load('lib_j2xml', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('lib_j2xml', JPATH_SITE, null, true);
		
		$db = JFactory::getDBO();
		$user = JFactory::getUser();
		
		$this->_nullDate = $db->getNullDate();
		$this->_user_id = $user->get('id');
		$this->_now = ((class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge'))) ? JFactory::getDate()->format("%Y-%m-%d-%H-%M-%S") : JFactory::getDate()->toFormat("%Y-%m-%d-%H-%M-%S");
		$this->_option = (PHP_SAPI != 'cli') ? JRequest::getCmd('option') : 'cli_'.strtolower(get_class(JApplicationCli::getInstance()));

		$execute = (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge')) ? 'execute' : 'query';				
		$db->setQuery("
			CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
				`id` int(10) unsigned NOT NULL,
				`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
				`title` varchar(100) NOT NULL DEFAULT ''
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
			")->$execute();
		$db->setQuery("
			TRUNCATE TABLE
				`#__j2xml_usergroups`;
			")->$execute();
		$db->setQuery("
			INSERT INTO
				`#__j2xml_usergroups`
			SELECT
				`id`,`parent_id`,CONCAT('[\"',REPLACE(`title`,'\"','\\\"'),'\"]')
			FROM
				`#__usergroups`;
			")->$execute();
		do {
			$db->setQuery("
				UPDATE
					`#__j2xml_usergroups` j
				INNER JOIN
					`#__usergroups` g
				ON
					j.parent_id = g.id
				SET
					j.parent_id = g.parent_id,
					j.title = CONCAT('[\"',REPLACE(`g`.`title`,'\"','\\\"'), '\",', SUBSTR(`j`.`title`,2));
				")->$execute();
			$n = $db->setQuery("
				SELECT
					COUNT(*)
				FROM
					`#__j2xml_usergroups`
				WHERE
					`parent_id` > 0;
				")->loadResult();
		} while ($n > 0);
		$db->setQuery("
			INSERT INTO
				`#__j2xml_usergroups`
			SELECT
				`id`,`parent_id`,`title`
			FROM
				`#__usergroups`;
			")->$execute();
		$this->_usergroups = $db->setQuery("SELECT `title`,`id` FROM `#__j2xml_usergroups`")->loadAssocList('title','id');
	}
	
	function import($xml, $params)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		//gc_enable(); // Enable Garbage Collector
		$db = JFactory::getDBO();
	
		$import_users = $params->get('import_users', '1');
		$keep_user_id = $params->get('keep_user_id', '0');
		$keep_user_attribs = $params->get('keep_user_attribs', '1');
		$execute = (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge')) ? 'execute' : 'query';
		
		if ($import_users)
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_users/models/user.php';
			JFactory::getLanguage()->load('com_users', JPATH_SITE);
			
			$autoincrement = 0;
			$query = "SELECT max(`id`) from #__users";
			$db->setQuery($query);
			$maxid = $db->loadResult();
			
			foreach($xml->xpath("//j2xml/user[not(username = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);
				
				$registry = new Registry($data['params']);
				$data['params'] = $registry->toArray();
				
				if (isset($data['group']))
				{
					$data['groups'][] = $this->getUsergroupId($data['group']);
					unset($data['group']);
				}
				elseif (isset($data['grouplist']))
				{
					$data['groups'] = array();
					foreach ($data['grouplist'] as $v)
						$data['groups'][] = $this->getUsergroupId($v);
					unset($data['grouplist']);
				}
				
				if (isset($data['password']))
				{
					$data['password_crypted'] = $data['password'];
					$data['password2'] = $data['password'] = JText::_('LIB_J2XML_PASSWORD_NOT_AVAILABLE');
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
				$query = 'SELECT id'
					. ' FROM #__users'
//					. ' WHERE'. (($keep_user_id == 1)
//					? ' id = '.$user_id
//					: ' username = '.$db->q($data['username'])
//					)
					. ' WHERE username = '.$db->q($data['username'])
					;
				$db->setQuery($query);
				$data['id'] = $db->loadResult();
				if (!$data['id'] || ($import_users == 2))
				{
					$user = new UsersModelUser();
					$result = $user->save($data);

					$db->setQuery('SELECT id FROM #__users WHERE username = '.$db->q($data['username']));
					if ($id = $db->loadResult())
					{
						
						if ($error = $user->getError())
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USER_IMPORTED_WITH_ERRORS', $data['name']),JLOG::WARNING,'lib_j2xml'));
							JLog::add(new JLogEntry($error,JLOG::WARNING,'lib_j2xml'));
						}
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USER_IMPORTED', $data['name']),JLOG::INFO,'lib_j2xml'));
						}
						if(isset($data['password_crypted']))
						{
							// set password
							$db  = JFactory::getDbo();
							$query = $db->getQuery(true)
								->update('#__users')
								->set($db->qn('password') . ' = ' . $db->q($data['password_crypted']))
								->where($db->qn('id') . ' = ' . $id)
								;
							$db->setQuery($query);
							$db->execute();
	
							if ($user_id && !$data['id'] && ($keep_user_id == 1))
							{
								$id = $user->getState('user.id');
								$query = "UPDATE #__users SET id = {$user_id} WHERE id = {$id}";
								$db->setQuery($query);
								$db->query();
								$query = "UPDATE #__user_usergroup_map SET user_id={$user_id} WHERE user_id={$id}";
								$db->setQuery($query);
								$db->query();
								if ($user_id >= $autoincrement)
								{
									$autoincrement = $user_id + 1;
								}
							}
						}
					}
					else 
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USER_NOT_IMPORTED', $data['name']),JLOG::ERROR,'lib_j2xml'));
					}
				}
			}
			if ($autoincrement)
			{
				 if ($autoincrement > $maxid)
				 {
					 $query = "ALTER TABLE #__users AUTO_INCREMENT = {$autoincrement}";
					 $db->setQuery($query);
					 $db->query();
				 }
			}
		}

		// import view levels
		foreach($xml->xpath("//j2xml/viewlevel[not(title = '')]") as $record)
		{
			$this->prepareData($record, $data, $params);
				
			$id = $data['id'];
			$query = 'SELECT id, title'
				. ' FROM #__viewlevels'
				. ' WHERE title = '.$db->Quote($data['title'])
				;
			$db->setQuery($query);
			$viewlevel = $db->loadObject();
			if (!$viewlevel)
			{
				$table = JTable::getInstance('viewlevel');
				if (!$viewlevel)
				{
					$data['id'] = null;
				}
				else
				{
					$data['id'] = $viewlevel->id;
					$table->load($data['id']);
				}
				
				// Add rules to the viewlevel data.
				$rules_id = array();
				if (isset($data['rule']))
				{
					$rules_id[] = $data['rule'];
					unset($data['rule']);
				}
				if (isset($data['ruleslist']))
				{
					foreach ($data['ruleslist'] as $v)
						$rules_id[] = $v;
					unset($data['ruleslist']);
				}
				for($i = 0; $i < count($rules_id); $i++)
				{
					$usergroup = $rules_id[$i];
					if (isset($usergroups[$usergroup]))
						$rules_id[$i] = $usergroups[$usergroup];
					else
					{
						$groups = json_decode($usergroup);
						$g = array();
						$id = 0;
						for ($j = 0; $j < count($groups); $j++)
						{
							$g[] = $groups[$j];
							$group = json_encode($g, JSON_NUMERIC_CHECK);
							if (isset($usergroups[$group]))
							{
								$id = $usergroups[$group];
							}
							else // if import usergroup
							{
								$u = JTable::getInstance('Usergroup');
								$u->save(array('title'=>$groups[$j], 'parent_id'=>$id));
								$usergroups[$group] = $id = $u->id;
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $group),JLOG::INFO,'lib_j2xml'));
							}
						}
						$rules_id[$i] = $id;
					}
				}
				$data['rules'] = json_encode($rules_id, JSON_NUMERIC_CHECK);

				//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));				
				if ($table->save($data))
				{
					JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_VIEWLEVEL_IMPORTED', $table->title),JLOG::INFO,'lib_j2xml'));
				}
				else
				{
					JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_VIEWLEVEL_NOT_IMPORTED', $data['title']),JLOG::ERROR,'lib_j2xml'));
					JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
				}
				$table = null;
			}
		}

		$import_categories = $params->get('import_categories', '1');
		$keep_id = $params->get('keep_id', '0');
		
		if ($import_categories)
		{
			JLog::add(new JLogEntry('*** Importing categories... ***', JLOG::DEBUG, 'lib_j2xml'));
			foreach($xml->xpath("//j2xml/category[not(title = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);
				// TODO: check extension
				
				$alias = $data['alias']; // = JApplication::stringURLSafe($data['alias']);
				$id = $data['id'];
				$path = $data['path'];
		
				$i = strrpos($path, '/');
				if ($i === false) {
					$data['parent_id'] = 1;
				} else {
					$data['parent_id'] = self::getCategoryId(substr($path, 0, $i), $data['extension']);
				}
				$query = 'SELECT id, title'
					. ' FROM #__categories'
					. ' WHERE extension = '. $db->Quote($data['extension'])
					. ' AND'. ((($keep_id == 1) && ($id > 1)) ? ' id = '.$id : ' path = '.$db->Quote($path))
					;
				JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
				$db->setQuery($query);
				$category = $db->loadObject();
				
				if (!$category || ($import_categories == 2))
				{
					$table = JTable::getInstance('category');
	
					if (!$category && ($keep_id == 1))
					{
						$query = 'SELECT id, title'
							. ' FROM #__categories'
							. ' WHERE path = '.$db->Quote($path)
							. ' AND extension = '. $db->Quote($data['extension'])
							;
						JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
						$db->setQuery($query);
						$category = $db->loadObject();
					}					
					
					if (!$category) // new category
					{
						$data['id'] = null;
						/*
						if ($keep_access > 0)
							$data['access'] = $keep_access;
						if ($keep_state < 2)
							// Force the state
							$data['published'] = $keep_state;
							//else keep the original state
	
						if (!$keep_attribs)
							$data['params'] = '{"category_layout":"","image":""}';
						*/
						$table->setLocation($data['parent_id'], 'last-child');
					}
					else // category already exists
					{
						$data['id'] = $category->id;
						$table->load($data['id']);
						/*		
						if ($keep_access > 0)
							// don't modify the access level
							$data['access'] = null;

						if ($keep_state != 0)
							// don't modify the state
							$data['published'] = null;
						//else keep the original state

						if (!$keep_attribs)
							$data['params'] = null;
							
						if (!$keep_author)
						{
							$data['created'] = null;
							$data['created_user_id'] = null;
							$data['created_by_alias'] = null;
							$data['modified'] = $now;
							$data['modified_user_id'] = $user_id;
							$data['version'] = $table->version + 1;
						}
						else // save default values
						{
							$data['created'] = $now;
							$data['created_user_id'] = $user_id;
							$data['created_by_alias'] = null;
							$data['modified'] = $this->_nullDate;
							$data['modified_user_id'] = null;
							$data['version'] = 1;
						}
						*/							
					}
										
					//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));
					if ($table->save($data))
					{
						if (!$category && ($keep_id == 1) && ($id > 1))
						{
							try
							{
								$query = "UPDATE #__categories SET `id` = {$id} WHERE `id` = {$table->id}";
								$db->setQuery($query);
								$db->query();
								$table->id = $id;
	
								$query = "UPDATE #__assets SET `name` = '{$data['extension']}.category.{$id}' WHERE `id` = {$table->asset_id}";
								$db->setQuery($query);
								$db->query();
	
								$query = "SELECT max(`id`)+1 from #__categories";
								$db->setQuery($query);
								$maxid = $db->loadResult();
	
								$query = "ALTER TABLE #__categories AUTO_INCREMENT = {$maxid}";
								$db->setQuery($query);
								$db->query();
							}
							catch(Exception $ex)
							{
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title), JLOG::WARNING, 'lib_j2xml'));
							}
						}
						// Rebuild the tree path.
						$table->rebuildPath();

						if ($keep_id && ($id > 0) && ($id != $table->id))
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title, $id, $table->id), JLOG::WARNING, 'lib_j2xml'));
						else
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
			}
		}
		
		$import_content = $params->get('import_content', '2');
		$import_images = $params->get('import_images', '1');
		
		$keep_access = $params->get('keep_access', '0');
		$keep_state = $params->get('keep_state', '2');
		$keep_author = $params->get('keep_author', '1');
		$keep_category = $params->get('keep_category', '1');
		$keep_attribs = $params->get('keep_attribs', '1');
		$keep_metadata = $params->get('keep_metadata', '1');
		$keep_frontpage = $params->get('keep_frontpage', '1');
		$keep_rating = $params->get('keep_rating', '1');
		$keep_id = $params->get('keep_id', '0');
		$rebuild_links = $params->get('rebuild_links', '0');
			
		$users_id = array();
		/*
		$query = "SELECT * FROM #__users WHERE id = 42";
		$db->setQuery($query);
		$user = $db->loadObject();
		if ($user)
			$users_id['admin'] = 42;
		else
			$users_id['admin'] = 62;
		$users_id[0] = 0;
		*/


		if ($keep_frontpage)
		{
			$query = 'SELECT max(ordering)'
				. ' FROM #__content_frontpage'
				;
			$db->setQuery($query);
			$frontpage = (int)$db->loadResult();			
		}
		
		JLog::add(new JLogEntry('*** Importing tags... ***', JLOG::DEBUG, 'lib_j2xml'));
		foreach($xml->xpath("//j2xml/tag") as $record)
		{
			$this->prepareData($record, $data, $params);
				
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
			$data['id'] = 0;
				
			$path = $data['path'];
			$i = strrpos($path, '/');
			if ($i === false) {
				$data['parent_id'] = 1;
			} else {
				$parent_path = substr($path, 0, $i);
				if (!isset($parent_ids[$parent_path])) {
					$query = 'SELECT id'
						. ' FROM #__tags'
						. ' WHERE path = '. $db->Quote($parent_path)
						;
					$db->setQuery($query);
					$parent_ids[$parent_path] = $db->loadResult();
				}
				$data['parent_id'] = $parent_ids[$parent_path];
			}							
			$table = JTable::getInstance('Tag', 'TagsTable');
			$table->setLocation($data['parent_id'], 'last-child');
			//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));
			$table->save($data);

			JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_TAG_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
		}
		
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		JLog::add(new JLogEntry('*** Importing articles... ***', JLOG::DEBUG, 'lib_j2xml'));

		$query = 'SELECT id, path'
			. ' FROM #__categories'
			. ' WHERE id = '.$params->get('category')
			. ' AND extension = '. $db->q('com_content')
			;
		$db->setQuery($query);
		
		if ($default_cat = $db->loadObject())
		{
			foreach($xml->xpath("//j2xml/content[not(name = '')]") as $record)
			{				
				$this->prepareData($record, $data, $params);
	
				$id = $data['id'];
				if (empty($data['alias']))
				{
					$data['alias'] = $data['title'];
					$data['alias'] = str_replace(' ', '-', $data['alias']);
				}
				$alias = $data['alias'];
				$catid = $data['catid'];
				
				$category_path = $data['catid'];
				
				// force category
				if ($keep_category == 2)
				{
					$data['sectionid'] = 0;
					$data['catid'] = $params->get('category');
				}
				else //if ($keep_category == 1)
				{
					// keep category
					if (!isset($data['sectionid']) && !isset($data['catid']))
					{
						// uncategorised
						$data['catid'] = $default_cat->id;
						$category_path = $default_cat->path;
					}
					else if (isset($categories_id['com_content/'.$data['catid']]))
					{
						// category already loaded
						$data['catid'] = $categories_id['com_content/'.$data['catid']];
					}
					else
					{
						// load category
						$query = 'SELECT id'
							. ' FROM #__categories'
							. ' WHERE path = '.$db->Quote($data['catid'])
							. ' AND extension = '.$db->Quote('com_content')
							;
						$db->setQuery($query);
						$category_id = (int)$db->loadResult();
						if ($category_id > 0)
						{
							$categories_id['com_content/'.$data['catid']] = $category_id;
							$data['catid'] = $category_id;
						}
						else
						{
							$data['catid'] = $default_cat->id;
							$category_path = $default_cat->path;
						}
					}
				}
				
				if ($keep_id == 1)
					$query = 'SELECT id, title'
						. ' FROM #__content'
						. ' WHERE id = '.$id
						;
				else
					$query = 'SELECT #__content.id, #__content.title'
						. ' FROM #__content LEFT JOIN #__categories'
						. ' ON #__content.catid = #__categories.id'
						. ' WHERE #__categories.path = '. $db->Quote($category_path)
						. ' AND #__content.alias = '. $db->Quote($alias)
						;
				$db->setQuery($query);
				$content = $db->loadObject();
					
				$table = JTable::getInstance('content');
				
				if (!$content || $import_content == 2)			
				{
					$data['checked_out'] = 0;
					$data['checked_out_time'] = $this->_nullDate;
					
					if (!$content)
					{ // new article
						$isNew = true; 
						$data['id'] = null;
						if ($keep_access > 0)
							$data['access'] = $keep_access;
						if ($keep_state < 2)
							// Force the state
							$data['state'] = $keep_state;
						
						if (!$keep_attribs)
							$data['attribs'] = '{"category_layout":"","image":""}';
						
						if (!$keep_metadata)
						{
							$data['metadata'] = '{"author":"","robots":""}';
							$data['metakey'] = '';
							$data['metadesc'] = '';
						}
					}
					else // article already exists
					{
						$isNew = false; 
						$data['id'] = $content->id;
	
						if ($keep_access > 0)
							// don't modify the access level
							$data['access'] = null;
						
						if ($keep_state != 0)  
							// don't modify the state
							$data['state'] = null;
						//else keep the original state		
	
						if (!$keep_attribs)
							$data['attribs'] = null;
						
						if (!$keep_metadata)
						{
							$data['metadata'] = null;
							$data['metakey'] = null;
							$data['metadesc'] = null;
						}
					}
												
					if ($keep_author)
					{
						if (isset($users_id[$data['created_by']]))
							$data['created_by'] = $users_id[$data['created_by']];
						else
						{
							$query = 'SELECT id'
								. ' FROM #__users'
								. ' WHERE username = '. $db->Quote($data['created_by'])
								;
							$db->setQuery($query);
							$userid = (int)$db->loadResult();
							if ($userid > 0)
							{
								$users_id[$data['created_by']] = $userid;
								$data['created_by'] = $userid;
							}
							else
								$data['created_by'] = $this->_user_id;
						}
						if (isset($data['modified_by']))
						{
							if (isset($users_id[$data['modified_by']]))
								$data['modified_by'] = $users_id[$data['modified_by']];
							else
							{
								$query = 'SELECT id'
									. ' FROM #__users'
									. ' WHERE username = '. $db->Quote($data['modified_by'])
									;
								$db->setQuery($query);
								$userid = (int)$db->loadResult();
								if ($userid > 0)
								{
									$users_id[$data['modified_by']] = $userid;
									$data['modified_by'] = $userid;
								}
								else
									$data['modified_by'] = $this->_user_id;
							}
						}
					}
					else if ($content)
					{
						$data['created'] = null;
						$data['created_by'] = null; 
						$data['created_by_alias'] = null; 				
						$data['modified'] = null; 
						$data['modified_by'] = null; 
						$data['version'] = null; 
					}
					else
					{
						$data['created'] = $now;
						$data['created_by'] = $user_id; 
						$data['created_by_alias'] = null; 				
						$data['modified'] = $this->_nullDate; 
						$data['modified_by'] = null; 
						$data['version'] = 1; 
					}
	
					if (!$keep_frontpage)
						$data['featured'] = 0;
					elseif ($data['featured'] > 0)
					{
						$data['ordering'] = $data['featured'];
						$data['featured'] = 1;
					}
	/*
					if (isset($data['sourcelist']))
					{
						$attribs = json_decode($data['attribs']);
						$attribs->sourcelist = $data['sourcelist'];
						$data['attribs'] = json_encode($attribs, JSON_NUMERIC_CHECK);
					}
					elseif (isset($data['source']))
					{
						$attribs = json_decode($data['attribs']);
						$attribs->sourcelist = array($data['source']);
						$data['attribs'] = json_encode($attribs, JSON_NUMERIC_CHECK);
					}
	*/				
					$table->bind($data);
					
					if (class_exists('JHelperTags'))
					{
						$tags = array();
						if (isset($data['tag']))
							$tags[] = $data['tag'];
						elseif (isset($data['taglist']))
						{
							foreach ($data['taglist'] as $v)
								$tags[] = $v;
						}
						$table->newTags = eshHelperTags::convertPathsToIds($tags);
					}
					
					// Trigger the onContentBeforeSave event.
					$result = $dispatcher->trigger('onContentBeforeSave', array($this->_option.'.article', &$table, $isNew));
					
					if (!in_array(false, $result, true))
					{
						// Undefined currentAssetId fix
						if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt'))
						{
							$error_level = error_reporting();
							error_reporting(0);
						}						
						if ($table->store())
						{
							if (!$content && ($keep_id == 1))
							{
								$query = "UPDATE #__content SET `id` = {$id} WHERE `id` = {$table->id}";
								$db->setQuery($query);
								$db->query();
								$table->id = $id;
								$query = "UPDATE #__assets SET `name` = 'com_content.article.{$id}' WHERE `id` = {$table->asset_id}";
								$db->setQuery($query);
								$db->query();
								
								$query = "SELECT max(`id`)+1 from #__content";
								$db->setQuery($query);
								$maxid = $db->loadResult();
										
								$query = "ALTER TABLE #__content AUTO_INCREMENT = {$maxid}";
								$db->setQuery($query);
								$db->query();
							}
							
							if ($keep_frontpage)
							{
								if ($data['featured'] == 0)
									$query = "DELETE FROM #__content_frontpage WHERE content_id = ".$table->id;
								else if($keep_id)
									$query = 
										  ' INSERT IGNORE INTO `#__content_frontpage`'
										. ' SET content_id = '.$table->id.','
										. '     ordering = '.$data['ordering'];
								else
								{
									$frontpage++;
									$query = 
										  ' INSERT IGNORE INTO `#__content_frontpage`'
										. ' SET content_id = '.$table->id.','
										. '     ordering = '.$frontpage;
								}
								$db->setQuery($query);
								$db->query();
							}
		
							if ($keep_rating)
							{
								if (isset($data['rating_count']))
									if ($data['rating_count'] > 0)
									{
										$rating = new stdClass();
										$rating->content_id = $table->id;
										$rating->rating_count = $data['rating_count'];
										$rating->rating_sum = $data['rating_sum'];
										$rating->lastip = $_SERVER['REMOTE_ADDR'];	
										try {
											$db->insertObject('#__content_rating', $rating);
										} catch (Exception $e) {
											$db->updateObject('#__content_rating', $rating, 'content_id');
										}
									}
									else
									{
										$query = "DELETE FROM `#__content_rating` WHERE `content_id`=".$table->id;
										$db->setQuery($query);
										$db->query();
									}
							}
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
	
							// Trigger the onContentAfterSave event.
							$dispatcher->trigger('onContentAfterSave', array($this->_option.'.article', &$table, $isNew));
						}
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'].' (id='.$id.')'), JLOG::ERROR, 'lib_j2xml'));
							if ($data['catid'])
								JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
							else
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $catid), JLOG::ERROR, 'lib_j2xml'));
						}
						// Undefined currentAssetId fix
						if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt'))
							error_reporting($error_level);
					}
				}
			}
		}

		/*
		 * Import Weblinks
		*/
		// Check if component is installed
		$db = JFactory::getDbo();
		$db->setQuery("SELECT enabled FROM #__extensions WHERE name = 'com_weblinks'");
		$is_enabled = $db->loadResult();
		if ($is_enabled)
		{
			JLog::add(new JLogEntry('*** Importing weblinks... ***', JLOG::DEBUG, 'lib_j2xml'));
			$import_weblinks = $params->get('import_weblinks', '1');
			foreach($xml->xpath("//j2xml/weblink[not(title = '')]") as $record)
			{
				$attributes = $record->attributes();
				$data = array();
				foreach($record->children() as $key => $value)
					$data[trim($key)] = trim($value);
				$alias = $data['alias'];
				$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');
				$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
					
				$query = 'SELECT id, title'
					. ' FROM #__weblinks'
					. ' WHERE alias = '. $db->Quote($alias)
					;
				$db->setQuery($query);
				$weblink = $db->loadObject();
									
				if (!$weblink || $import_weblinks)
				{
					$data['checked_out'] = 0;
					$data['checked_out_time'] = $this->_nullDate;
			
					$table = JTable::getInstance('Weblink', 'WeblinksTable');
			
					if (!$weblink)
					{ // new weblink
						$data['id'] = null;
					}
					else // weblink already exists
					{
						$data['id'] = $weblink->id;
						$table->load($data['id']);
					}
										
					if (isset($categories_id['com_weblinks/'.$data['catid']]))
					{
						// category already loaded
						$data['catid'] = $categories_id['com_weblinks/'.$data['catid']];
						JLog::add(new JLogEntry('com_weblinks/'.$data['catid'].' -> '.$data['catid'], JLOG::DEBUG, 'lib_j2xml'));
					}
					else
					{
						// load category
						$query = 'SELECT id'
							. ' FROM #__categories'
							. ' WHERE path = '. $db->Quote($data['catid'])
							. ' AND extension = '. $db->Quote('com_weblinks')
	//						. ' AND level = 1'
							;
						JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
						$db->setQuery($query);
						$category_id = (int)$db->loadResult();
						if ($category_id > 0)
						{
							$categories_id['com_weblinks/'.$data['catid']] = $category_id;
							$data['catid'] = $category_id;
						}
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $data['catid']), JLOG::ERROR, 'lib_j2xml'));
							continue;
						}
					}
					// Trigger the onContentBeforeSave event.
					$table->bind($data);
					if ($table->store())
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_WEBLINK_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
						// Trigger the onContentAfterSave event.
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
			}
		}

		/*
		 * Import Buttons
		 */
		// Check if component is installed
		$db = JFactory::getDbo();
		$db->setQuery("SELECT enabled FROM #__extensions WHERE name = 'com_buttons'");
		$is_enabled = $db->loadResult();
		if ($is_enabled)
		{
			$import_buttons = $params->get('import_buttons', '1');
			foreach($xml->xpath("//j2xml/button[not(title = '')]") as $record)
			{
				$attributes = $record->attributes();
				$data = array();
				foreach($record->children() as $key => $value)
					$data[trim($key)] = trim($value);
					$alias = $data['alias'];
					$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');
					$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
						
					$query = 'SELECT id, title'
						. ' FROM #__buttons'
						. ' WHERE alias = '. $db->Quote($alias)
						;
					$db->setQuery($query);
					$button = $db->loadObject();
										
					if (!$button || $import_buttons)
					{
						$data['checked_out'] = 0;
						$data['checked_out_time'] = $this->_nullDate;
							
						$table = JTable::getInstance('Button', 'ButtonsTable');
							
						if (!$button)
						{ // new button
							$data['id'] = null;
						}
						else // button already exists
						{
							$data['id'] = $button->id;
							$table->load($data['id']);
						}

						if (isset($categories_id['com_buttons/'.$data['catid']]))
						{
							// category already loaded
							$data['catid'] = $categories_id['com_buttons/'.$data['catid']];
						}
						else
						{
							// load category
							$query = 'SELECT id'
								. ' FROM #__categories'
								. ' WHERE alias = '. $db->Quote($data['catid'])
								. ' AND extension = '. $db->Quote('com_buttons')
								//						. ' AND level = 1'
								;
							$db->setQuery($query);
							$category_id = (int)$db->loadResult();
							if ($category_id > 0)
							{
								$categories_id['com_buttons/'.$data['catid']] = $category_id;
								$data['catid'] = $category_id;
							}
							else
							{
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_BUTTON_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $data['catid']), JLOG::ERROR, 'lib_j2xml'));
								continue;
							}
						}
						// Trigger the onContentBeforeSave event.
						$table->bind($data);
						if ($table->store())
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_BUTTON_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
							// Trigger the onContentAfterSave event.
						}
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_BUTTON_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
							JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
						}
						$table = null;
					}
			}
		}
		
		if ($import_images)
		{
			jimport('joomla.filesystem.folder');
			foreach($xml->img as $image)
			{ 
				$src = JPATH_SITE.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, urldecode(html_entity_decode($image['src'], ENT_QUOTES, 'UTF-8'))); 
				$data = $image;
				if (!file_exists($src) || ($import_images == 2))
				{
					// many thx to Stefanos Tzigiannis
					$folder = dirname($src);
					if (!JFolder::exists($folder)) {
						if (JFolder::create($folder))
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED', $folder),JLOG::INFO,'lib_j2xml'));
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ERROR_CREATING_FOLDER', $folder),JLOG::ERROR,'lib_j2xml'));
							break;
						}
					}
 					if (JFile::write($src, base64_decode($data)))
 						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_IMAGE_IMPORTED', $image['src']),JLOG::INFO,'lib_j2xml'));
					else
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_IMAGE_NOT_IMPORTED', $image['src']),JLOG::ERROR,'lib_j2xml'));
				}
			}
		} 
		
		if ($import_users)
		{
			foreach($xml->xpath("//j2xml/contact[not(alias = '')]") as $record)
			{
				$data = array();
				foreach($record->children() as $key => $value)
				{
					if (count($value->children()) === 0)
						$data[trim($key)] = trim($value);
					else
						foreach ($value->children() as $v)
							$data[trim($key)][] = trim($v);
				}
				$id = $data['id'];
				$alias = $data['alias'];
				$data['name'] = html_entity_decode($data['name'], ENT_QUOTES, 'UTF-8');
				$query = 'SELECT id, alias'
					. ' FROM #__contact_details'
					. ' WHERE'. (($keep_user_id == 1)
					? ' id = '.$id
					: ' alias = '.$db->Quote($alias)
					)
					;
				$db->setQuery($query);
				$contact = $db->loadObject();
				if (!$contact || ($import_users == 2))
				{
					$table = JTable::getInstance('contact','ContactTable');
					if (!$contact)
					{
						$data['id'] = null;
					}
					else
					{
						$data['id'] = $contact->id;
						$table->load($data['id']);
					}

					if (!$keep_user_attribs)
						$data['params'] = null;

					if (isset($categories_id['com_contact/'.$data['catid']]))
					{
						// category already loaded
						$data['catid'] = $categories_id['com_contact/'.$data['catid']];
					}
					else
					{
						// load category
						$query = 'SELECT id'
							. ' FROM #__categories'
							. ' WHERE alias = '. $db->Quote($data['catid'])
							. ' AND extension = '. $db->Quote('com_contact')
							// . ' AND level = 1'
						;
						$db->setQuery($query);
						$category_id = (int)$db->loadResult();
						if ($category_id > 0)
						{
							$categories_id['com_contact/'.$data['catid']] = $category_id;
							$data['catid'] = $category_id;
						}
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CONTACT_NOT_IMPORTED', $data['name']), JLOG::ERROR, 'lib_j2xml'));
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $data['catid']), JLOG::ERROR, 'lib_j2xml'));
							continue;
						}
					}					
					
					//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));
					if ($table->save($data))
					{
						if (!$contact && ($keep_user_id == 1))
						{
							$query = "UPDATE #__contact_details SET id = {$id} WHERE id = {$table->id}";
							$db->setQuery($query);
							$db->query();
							$table->id = $id;

							$query = "SELECT max(`id`)+1 from #__contact_details";
							$db->setQuery($query);
							$maxid = $db->loadResult();

							$query = "ALTER TABLE #__contact_details AUTO_INCREMENT = {$maxid}";
							$db->setQuery($query);
							$db->query();
						}
						$contacts_id[$alias] = $table->id;
						$contacts_title[$alias] = $table->name;
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CONTACT_IMPORTED', $table->name),JLOG::INFO,'lib_j2xml'));
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CONTACT_NOT_IMPORTED', $data['name']),JLOG::ERROR,'lib_j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
				elseif ($contact)
				{
					$contacts_id[$alias] = $user->id;
					$contacts_title[$alias] = $user->name;
				}
			}
		}
		
		//gc_disable(); // Disable Garbage Collector

		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterImport event.
		$dispatcher->trigger('onAfterImport', array($this->_option.'.'.__FUNCTION__, &$xml, $params));
	}	
	
	static function clean()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		$db = JFactory::getDBO();
		$execute = (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge')) ? 'execute' : 'query';
		
		// tag
		if (version_compare(JPlatform::RELEASE, '12', 'ge'))
		{
			$db->setQuery('TRUNCATE `#__contentitem_tag_map`')->$execute();
			$db->setQuery('TRUNCATE `#__tags`')->$execute();
			$db->setQuery("INSERT INTO `#__tags` (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `title`, `alias`, `note`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`, `metadesc`, `metakey`, `metadata`, `created_user_id`, `created_time`, `created_by_alias`, `modified_user_id`, `modified_time`, `images`, `urls`, `hits`, `language`, `version`, `publish_up`, `publish_down`) VALUES (1, 0, 0, 1, 0, '', 'ROOT', 'root', '', '', 1, 0, '0000-00-00 00:00:00', 1, '', '', '', '', 0, '2011-01-01 00:00:01', '', 0, '0000-00-00 00:00:00', '', '', 0, '*', 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00')")->$execute();				
		}
		
		// contact
		$db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_contact.contact')")->$execute();
		$db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_contact.contact.%'")->$execute();
		$db->setQuery("TRUNCATE `#__contact_details`")->$execute();
		$db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_contact.category')")->$execute();
		$db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_contact.category.%' AND `Title` <> 'Uncategorised'")->$execute();
		$db->setQuery("DELETE FROM `#__categories` WHERE `extension` = 'com_contact' AND `Title` <> 'Uncategorised'")->$execute();
		JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_CONTACTS_CLEANED'),JLOG::NOTICE,'lib_j2xml'));
		
		// content
		$db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_content.article')")->$execute();
		$db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_content.article.%'")->$execute();
		$db->setQuery("TRUNCATE `#__content`")->$execute();
		$db->setQuery("TRUNCATE `#__content_frontpage`")->$execute();
		$db->setQuery("TRUNCATE `#__content_rating`")->$execute();
		$db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_content.category')")->$execute();
		$db->setQuery("DELETE FROM `#__ucm_content` WHERE `core_type_alias`='com_content.article'")->$execute();
		$db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_content.category.%' AND `Title` <> 'Uncategorised'")->$execute();
		$db->setQuery("DELETE FROM `#__categories` WHERE `extension` = 'com_content' AND `Title` <> 'Uncategorised'")->$execute();
		JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_CONTENT_CLEANED'),JLOG::NOTICE,'lib_j2xml'));
		
		// users
		$db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_users.user')")->$execute();
		$db->setQuery("DELETE FROM `#__users` WHERE `id` NOT IN (SELECT user_id FROM `#__user_usergroup_map` WHERE group_id = 8)")->$execute();
		JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_USERS_CLEANED'),JLOG::NOTICE,'lib_j2xml'));
		
		// viewlevels
		$db->setQuery("DELETE FROM `#__viewlevels` WHERE `id` > 6")->$execute();
		JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_VIEWLEVELS_CLEANED'),JLOG::NOTICE,'lib_j2xml'));
		
		// usergroups
		$db->setQuery("DELETE FROM `#__usergroups` WHERE `id` > 9")->$execute();
		JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_USERGROUPS_CLEANED'),JLOG::NOTICE,'lib_j2xml'));		
		/*
		JPluginHelper::importPlugin('j2xml');
		$dispatcher = JDispatcher::getInstance();
		// Trigger the onAfterImport event.
		$dispatcher->trigger('onClean', array($this->_option.'.'.__FUNCTION__, &$xml, $params));
		*/
	}
	
	function getArticleId($path)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		$db = JFactory::getDBO();
		$i = strrpos($path, '/');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('c.id'));
		$query->from($db->quoteName('#__content', 'c'));
		$query->join('INNER', $db->quoteName('#__categories', 'cc').' ON '.$db->quoteName('c.catid').'='.$db->quoteName('cc.id'));
		$query->where($db->quoteName('cc.extension').'='.$db->quote('com_content'));
		$query->where($db->quoteName('c.alias').'='.$db->quote(substr($path, $i + 1)));
		$query->where($db->quoteName('cc.path').'='.$db->quote(substr($path, 0, $i)));
		$db->setQuery($query);
		$article_id = $db->loadResult();
		JLog::add(new JLogEntry($path.' -> '.$article_id, JLOG::DEBUG, 'lib_j2xml'));
		return $article_id;
	}
	
	function getUserId($username, $default_user_id)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		$db = JFactory::getDBO();		
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('username').'='.$db->quote($username));
		$db->setQuery($query);
		if (!($user_id = $db->loadResult()))
			$user_id = $default_user_id;
		JLog::add(new JLogEntry($username.' -> '.$user_id, JLOG::DEBUG, 'lib_j2xml'));
		return $user_id;
	}
	
	function getUsergroupId($usergroup)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		if (empty($usergroup))
		{
			$usergroup_id = JComponentHelper::getParams('com_users')->get('new_usertype');
		} 
		elseif (!is_numeric($usergroup))
		{
			$db = JFactory::getDBO();		
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->from($db->quoteName('#__j2xml_usergroups'));
			$query->where($db->quoteName('title').'='.$db->quote($usergroup));
			$db->setQuery($query);
			if (!($usergroup_id = $db->loadResult()))
			{
				$groups = json_decode($usergroup);
				$g = array();
				$usergroup_id = 0;
				for ($j = 0; $j < count($groups); $j++)
				{
					$g[] = $groups[$j];
					$group = json_encode($g, JSON_NUMERIC_CHECK);
					if (isset($this->_usergroups[$group]))
					{
						$usergroup_id = $this->_usergroups[$group];
					}
					else // if import usergroup
					{
						$u = JTable::getInstance('Usergroup');
						$u->save(array('title'=>$groups[$j], 'parent_id'=>$usergroup_id));
						$this->_usergroups[$group] = $usergroup_id = $u->id;
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $group),JLOG::INFO,'lib_j2xml'));
					}
				}
				if ($usergroup_id == 0)
					$usergroup_id = JComponentHelper::getParams('com_users')->get('new_usertype');			
			}
		}
		elseif ($usergroup > 0)
		{
			$usergroup_id = $usergroup;
		}
		else
		{
			$usergroup_id = JComponentHelper::getParams('com_users')->get('new_usertype');
		}
		JLog::add(new JLogEntry($usergroup.' -> '.$usergroup_id, JLOG::DEBUG, 'lib_j2xml'));
		return $usergroup_id;
	}
	
	function getAccessId($access)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		if (is_numeric($access)) return $access;
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__viewlevels'));
		$query->where($db->quoteName('title').'='.$db->quote($access));
		$db->setQuery($query);
		$access_id = $db->loadResult();
		if (!$access_id)
			$access_id = 3;
		JLog::add(new JLogEntry($access.' -> '.$access_id, JLOG::DEBUG, 'lib_j2xml'));
		return $access_id;
	}
	
	function getCategoryId($category, $extension)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		if (is_numeric($category)) return $category;
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__categories'));
		$query->where($db->quoteName('path').'='.$db->quote($category));
		$query->where($db->quoteName('extension').'='.$db->quote($extension));
		$db->setQuery($query);
		$category_id = $db->loadResult();
		if (!$category_id)
			$category_id = 1;
		JLog::add(new JLogEntry($extension.'/'.$category.' -> '.$category_id, JLOG::DEBUG, 'lib_j2xml'));
		return $category_id;
	}
	
	public function prepareData($record, &$data, $params)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		$data = array();
		foreach($record->children() as $key => $value)
		{
			if (count($value->children()) === 0)
				$data[trim($key)] = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8'); 
			else
				foreach ($value->children() as $v)
					$data[trim($key)][] = html_entity_decode(trim($v), ENT_QUOTES, 'UTF-8');
		}
		$data['checked_out'] = 0;
		$data['checked_out_time'] = $this->_nullDate;
		if (isset($data['created_user_id']))
			$data['modified_user_id'] = self::getUserId($data['created_user_id'], $this->_user_id);
		if (isset($data['modified_user_id']))
			$data['modified_user_id'] = self::getUserId($data['modified_user_id'], 0);
		if (isset($data['access']))
			$data['access'] = self::getAccessId($data['access']);
		if (isset($data['publish_up']))
		{
			$date = new JDate($data['publish_up']);
			$data['publish_up'] = $date->toISO8601(false);
		}
		if (isset($data['publish_down']))
		{
			$date = new JDate($data['publish_down']);
			$data['publish_down'] = $date->toISO8601(false);
		}
		if (isset($data['created']))
		{
			$date = new JDate($data['created']);
			$data['created'] = $date->toISO8601(false);
		}
		if (isset($data['modified']))
		{
			$date = new JDate($data['modified']);
			$data['modified'] = $date->toISO8601(false);
		}
		//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));
	}

	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	static function stripInvalidXml($value)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));
		
		$ret = "";
		$current;
		if (empty($value))
		{
			return $ret;
		}
	
		$length = strlen($value);
		for ($i=0; $i < $length; $i++)
		{
			$current = ord($value{$i});
			if (($current == 0x9) ||
					($current == 0xA) ||
					($current == 0xD) ||
					(($current >= 0x20) && ($current <= 0xD7FF)) ||
					(($current >= 0xE000) && ($current <= 0xFFFD)) ||
					(($current >= 0x10000) && ($current <= 0x10FFFF)))
			{
				$ret .= chr($current);
			}
			else
			{
				$ret .= " ";
			}
		}
		return $ret;
	}
}
?>
