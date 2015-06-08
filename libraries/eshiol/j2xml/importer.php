<?php
/**
 * @version		15.6.249 libraries/eshiol/j2xml/importer.php
 * 
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.6.0
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

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_weblinks/tables');

//jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

if (class_exists('JHelperTags'))
	jimport('eshiol.j2xml.helper.tags');

class J2XMLImporter
{
	static function import($xml, $params)
	{
		//gc_enable(); // Enable Garbage Collector

		// Merge the default translation with the current translation
		$jlang = JFactory::getLanguage();
		$jlang->load('lib_j2xml', JPATH_SITE, 'en-GB', true);
		$jlang->load('lib_j2xml', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('lib_j2xml', JPATH_SITE, null, true);
		
		$db = JFactory::getDBO();
		$nullDate = $db->getNullDate();
		$user = JFactory::getUser();
		$user_id = $user->get('id');
		$now = ((class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge'))) ? JFactory::getDate()->format("%Y-%m-%d-%H-%M-%S") : JFactory::getDate()->toFormat("%Y-%m-%d-%H-%M-%S");
		$option = (PHP_SAPI != 'cli') ? JRequest::getCmd('option') : 'cli_'.strtolower(get_class(JApplicationCli::getInstance()));
		
		$import_content = $params->get('import_content', '2');
		$import_users = $params->get('import_users', '1');
		$import_categories = $params->get('import_categories', '1');
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
		
		$keep_user_id = $params->get('keep_user_id', '0');
		$keep_user_attribs = $params->get('keep_user_attribs', '1');
		
		$query = "SELECT id, path FROM #__categories"
			. " WHERE path = 'uncategorised'"
			. " AND extension = 'com_content'"
			;
		$db->setQuery($query);
		$uncategorised = $db->loadObject();

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

		if ($import_users)
		{
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
			$usergroups = $db->setQuery("SELECT `title`,`id` FROM `#__j2xml_usergroups`")->loadAssocList('title','id');

			foreach($xml->xpath("user[not(username = '')]") as $record)
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
				$data['username'] = html_entity_decode($data['username'], ENT_QUOTES, 'UTF-8');
				$alias = $data['username'];
				$id = $data['id'];
				$data['name'] = html_entity_decode($data['name'], ENT_QUOTES, 'UTF-8');
				$query = 'SELECT id, name'
					. ' FROM #__users'
					. ' WHERE'. (($keep_user_id == 1)
						? ' id = '.$id
						: ' username = '.$db->Quote($alias)
						)
					;
				$db->setQuery($query);
				$user = $db->loadObject();
				if (!$user || ($import_users == 2))
				{
					$table = JTable::getInstance('user');
					if (!$user)
					{
						$data['id'] = null;
					}
					else
					{
						$data['id'] = $user->id;
						$table->load($data['id']);
					}

					// Add the groups to the user data.
					$groups_id = array();
					if (isset($data['group']))
						$groups_id[] = $data['group'];
					if (isset($data['grouplist']))
						foreach ($data['grouplist'] as $v)
							$groups_id[] = $v;
					
					for($i = 0; $i < count($groups_id); $i++)
					{
						$usergroup = $groups_id[$i];
						
						if (isset($usergroups[$usergroup]))
							$groups_id[$i] = $usergroups[$usergroup];
						else 
						{
							$groups = json_decode($usergroup);
							$g = array();
							$id = 0;
							for ($j = 0; $j < count($groups); $j++)
							{
								$g[] = $groups[$j];
								$group = json_encode($g);
								if (isset($usergroups[$group]))
								{
									$id = $usergroups[$group];
								}
								else // if import usergroup
								{
									$u = JTable::getInstance('Usergroup');
									$u->save(array('title'=>$groups[$j], 'parent_id'=>$id));
									$usergroups[$group] = $id = $u->id;
									JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USERGROUP_IMPORTED', $group),JLOG::INFO,'j2xml'));
								}
							}
							$groups_id[$i] = $id;
						}
					}
					$data['groups'] = $groups_id;

					if (count($data['groups']) == 0)
						$data['groups']['Public'] = 1;
						
					if (!$keep_user_attribs)
						$data['attribs'] = null;

					if ($table->save($data))
					{
						if (!$user && ($keep_user_id == 1))
						{
							$query = "UPDATE #__users SET id = {$id} WHERE id = {$table->id}";
							$db->setQuery($query);
							$db->query();
							$query = "UPDATE #__user_usergroup_map SET user_id={$id} WHERE user_id={$table->id}";
							$db->setQuery($query);
							$db->query();
							$table->id = $id;

							$query = "SELECT max(`id`)+1 from #__users";
							$db->setQuery($query);
							$maxid = $db->loadResult();

							$query = "ALTER TABLE #__users AUTO_INCREMENT = {$maxid}";
							$db->setQuery($query);
							$db->query();
						}
						$users_id[$alias] = $table->id;
						$users_title[$alias] = $table->name;
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USER_IMPORTED', $table->name),JLOG::INFO,'j2xml'));
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_USER_NOT_IMPORTED', $data['name']),JLOG::ERROR,'j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'j2xml'));
					}
					$table = null;
				}
				elseif ($user)
				{
					$users_id[$alias] = $user->id;
					$users_title[$alias] = $user->name;
				}
			}
		}

		if ($import_categories)
		{
			foreach($xml->xpath("category[not(title = '')]") as $record)
			{
				$data = array();
				foreach($record->children() as $key => $value)
					$data[trim($key)] = trim($value);
				$alias = $data['alias']; // = JApplication::stringURLSafe($data['alias']);
				$id = $data['id'];
				$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');				
				$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
				
				$path = $data['path'];
				
				$i = strrpos($path, '/');
				if ($i === false) {
					$section_alias = '';
					$data['section'] = 1;
				} else {
					$section_alias = substr($path, 0, $i);
					if (!isset($categories_id[$data['extension'].'/'.$section_alias])) {
						$query = 'SELECT id, title'
							. ' FROM #__categories'
							. ' WHERE extension = '. $db->Quote($data['extension'])
							. ' AND path = '. $db->Quote($section_alias)
							;
						$db->setQuery($query);
						//list($section_id, $section_title) = $db->loadResultArray();
						$section_id = $db->loadColumn(0);
						$categories_id[$data['extension'].'/'.$section_alias] = $section_id[0];
						$section_title = $db->loadColumn(1);
						$categories_title[$data['extension'].'/'.$section_alias] = $section_title[0];
					}
					$data['section'] = $categories_id[$data['extension'].'/'.$section_alias];
				}
				if (($keep_id == 1) && ($id > 1))
				{
					$query = 'SELECT id, title'
						. ' FROM #__categories'
						. ' WHERE extension = '. $db->Quote($data['extension'])
						. ' AND id = '.$id
						;
				}		
				else
					$query = 'SELECT id, title'
						. ' FROM #__categories'
						. ' WHERE extension = '. $db->Quote($data['extension'])
						. ' AND path = '. $db->Quote($path)
						;
				$db->setQuery($query);
				$category = $db->loadObject();
				
				if (!$category || ($import_categories == 2))
				{
					$data['checked_out'] = 0;
					$data['checked_out_time'] = $nullDate;
					$table = JTable::getInstance('category');

					if (!$category) // new category
					{
						$data['id'] = null;
						if ($keep_access > 0)
							$data['access'] = $keep_access;
						if ($keep_state < 2)
							// Force the state
							$data['published'] = $keep_state;
						//else keep the original state
						
						if (!$keep_attribs)
							$data['params'] = '{"category_layout":"","image":""}';
						$table->setLocation($data['section'], 'last-child');
					}
					else // category already exists
					{
						$data['id'] = $category->id;
						$table->load($data['id']);
						
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
							$data['created_by'] = null; 
							$data['created_by_alias'] = null; 				
							$data['modified'] = $now; 
							$data['modified_by'] = $user_id; 
							$data['version'] = $table->version + 1; 
						}	
						else // save default values
						{
							$data['created'] = $now;
							$data['created_by'] = $user_id; 
							$data['created_by_alias'] = null; 				
							$data['modified'] = $nullDate; 
							$data['modified_by'] = null; 
							$data['version'] = 1; 
						}
					}
					$data['parent_id'] = $data['section'];
					
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
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title), JLOG::WARNING, 'j2xml'));
							}
						}
						// Rebuild the tree path.
						$table->rebuildPath();
						$categories_id[$data['extension'].'/'.$path] = $table->id;
						if ($section_alias)
							$table->title = $categories_title[$data['extension'].'/'.$section_alias].'/'.$table->title; 
						$categories_title[$data['extension'].'/'.$path] = $table->title;

						if ($keep_id && ($id > 0) && ($id != $table->id))
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title), JLOG::WARNING, 'j2xml'));
						else
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_IMPORTED', $table->title), JLOG::INFO, 'j2xml'));
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'j2xml'));
					}
					$table = null;
				}
				elseif ($category)
				{
					$categories_id[$data['extension'].'/'.$path] = $category->id;
					$categories_title[$data['extension'].'/'.$path] = $category->title;
				}
			}
		}

		if ($keep_frontpage)
		{
			$query = 'SELECT max(ordering)'
				. ' FROM #__content_frontpage'
				;
			$db->setQuery($query);
			$frontpage = (int)$db->loadResult();			
		}
		
		
		foreach($xml->xpath("tag") as $record)
		{
			$data = array();
			foreach($record->children() as $key => $value)
			{
				if ($value->children())
				{
					$d = array();
					foreach($value->children() as $v)
						$d[] = trim($v);
					$data[trim($key)] = $d;
				}
				else
					$data[trim($key)] = trim($value);
			}
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
			$data['id'] = 0;
			$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');
			$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
				
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
			$table->save($data);
			JError::raiseNotice(1, $data['title']);
		}
		
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		foreach($xml->xpath("content[not(title = '')]") as $record)
		{				
			$data = array();
			foreach($record->children() as $key => $value)
			{
				if ($value->children())
				{
					$d = array();
					foreach($value->children() as $v)
						$d[] = trim($v);
					$data[trim($key)] = $d;
				}
				else
					$data[trim($key)] = trim($value);
			}
			$id = $data['id'];
			if (empty($data['alias']))
			{
				$data['alias'] = $data['title'];
				$data['alias'] = str_replace(' ', '-', $data['alias']);
			}
			$alias = $data['alias'];
			$catid = $data['catid'];
			$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');							
			$data['introtext'] = html_entity_decode($data['introtext'], ENT_QUOTES, 'UTF-8');
			$data['fulltext'] = html_entity_decode($data['fulltext'], ENT_QUOTES, 'UTF-8');
				
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
					$data['catid'] = $uncategorised->id;
					$category_path = $uncategorised->path;
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
						$data['catid'] = $uncategorised->id;
						$category_path = $uncategorised->path;
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
				$data['checked_out_time'] = $nullDate;
				
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
							$data['created_by'] = $user_id;
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
								$data['modified_by'] = $user;
							}
							else
								$data['modified_by'] = $user_id;
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
					$data['modified'] = $nullDate; 
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
				$result = $dispatcher->trigger('onContentBeforeSave', array($option.'.article', &$table, $isNew));
				
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
									if (!$db->insertObject('#__content_rating', $rating))
										$db->updateObject('#__content_rating', $rating, 'content_id');
								}
								else
								{
									$query = "DELETE FROM `#__content_rating` WHERE `content_id`=".$table->id;
									$db->setQuery($query);
									$db->query();
								}
						}
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $table->title), JLOG::INFO, 'j2xml'));
						if (isset($data['sourcelist']))
							$table->sourcelist = $data['sourcelist'];
						elseif (isset($data['source']))
							$table->sourcelist = $data['source'];
						// Trigger the onContentAfterSave event.
						$dispatcher->trigger('onContentAfterSave', array($option.'.article', &$table, $isNew));
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'].' (id='.$id.')'), JLOG::ERROR, 'j2xml'));
						if ($data['catid'])
							JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'j2xml'));
						else
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $catid), JLOG::ERROR, 'j2xml'));
					}
					// Undefined currentAssetId fix
					if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt'))
						error_reporting($error_level);
				}
			}
		}
		
		/*
		 * Import Weblinks
		*/
		foreach($xml->xpath("weblink[not(title = '')]") as $record)
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
				$data['checked_out_time'] = $nullDate;
		
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
				}
				else
				{
					// load category
					$query = 'SELECT id'
						. ' FROM #__categories'
						. ' WHERE alias = '. $db->Quote($data['catid'])
						. ' AND extension = '. $db->Quote('com_weblinks')
//						. ' AND level = 1'
						;
					$db->setQuery($query);
					$category_id = (int)$db->loadResult();
					if ($category_id > 0)
					{
						$categories_id['com_weblinks/'.$data['catid']] = $category_id;
						$data['catid'] = $category_id;
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'j2xml'));
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $data['catid']), JLOG::ERROR, 'j2xml'));
						continue;
					}
				}
				// Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_WEBLINK_IMPORTED', $table->title), JLOG::INFO, 'j2xml'));
					// Trigger the onContentAfterSave event.
				}
				else
				{
					JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'j2xml'));
					JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'j2xml'));
				}
				$table = null;
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
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED', $folder),JLOG::INFO,'j2xml'));
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ERROR_CREATING_FOLDER', $folder),JLOG::ERROR,'j2xml'));
							break;
						}
					}
 					if (JFile::write($src, base64_decode($data)))
 						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_IMAGE_IMPORTED', $image['src']),JLOG::INFO,'j2xml'));
					else
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_IMAGE_NOT_IMPORTED', $image['src']),JLOG::ERROR,'j2xml'));
				}
			}
		} 
		//gc_disable(); // Disable Garbage Collector
	}	
	
	static function clean()
	{
		$db = JFactory::getDBO();
		$db->setQuery('TRUNCATE `#__content`');
		$db->execute();
		$db->setQuery('DELETE FROM `#__assets` WHERE `name` LIKE "com_content.article.%"');
		$db->execute();
		$db->setQuery('DELETE FROM `#__categories` WHERE `extension` = "com_content" AND `id` <> 2');
		$db->execute();
		$db->setQuery('DELETE FROM `#__assets` WHERE `name` LIKE "com_content.category.%" AND `name` <> "com_content.category.2"');
		$db->execute();
		if (version_compare(JPlatform::RELEASE, '12', 'ge'))
		{
			$db->setQuery('DELETE FROM `#__contentitem_tag_map` WHERE `type_alias` = "com_content.article"');
			$db->execute();
			$db->setQuery('DELETE FROM `#__tags` WHERE id > 1');
			$db->execute();
		}
		$db->setQuery('DELETE FROM `#__redirect_links` WHERE comment = "Generated by J2XML"');
		$db->execute();
	}
}
?>
