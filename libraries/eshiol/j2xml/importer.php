<?php
/**
 * @version		17.9.304 libraries/eshiol/j2xml/importer.php
 * 
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.6.0
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

			$this->_db = JFactory::getDBO();
			$this->_user = JFactory::getUser();

			$this->_nullDate = $this->_db->getNullDate();
			$this->_user_id = $this->_user->get('id');
			$this->_now = JFactory::getDate()->format("%Y-%m-%d-%H-%M-%S");
			$this->_option = (PHP_SAPI != 'cli') ? JFactory::getApplication()->input->getCmd('option') : 'cli_'.strtolower(get_class(JApplicationCli::getInstance()));

			$this->_db->setQuery("
			CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
				`id` int(10) unsigned NOT NULL,
				`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
				`title` varchar(100) NOT NULL DEFAULT ''
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
			")->execute();
			$this->_db->setQuery("
			TRUNCATE TABLE
				`#__j2xml_usergroups`;
			")->execute();
			$this->_db->setQuery("
			INSERT INTO
				`#__j2xml_usergroups`
			SELECT
				`id`,`parent_id`,CONCAT('[\"',REPLACE(`title`,'\"','\\\"'),'\"]')
			FROM
				`#__usergroups`;
			")->execute();
			do {
				$this->_db->setQuery("
				UPDATE
					`#__j2xml_usergroups` j
				INNER JOIN
					`#__usergroups` g
				ON
					j.parent_id = g.id
				SET
					j.parent_id = g.parent_id,
					j.title = CONCAT('[\"',REPLACE(`g`.`title`,'\"','\\\"'), '\",', SUBSTR(`j`.`title`,2));
				")->execute();
				$n = $this->_db->setQuery("
				SELECT
					COUNT(*)
				FROM
					`#__j2xml_usergroups`
				WHERE
					`parent_id` > 0;
				")->loadResult();
			} while ($n > 0);
			$this->_db->setQuery("
			INSERT INTO
				`#__j2xml_usergroups`
			SELECT
				`id`,`parent_id`,`title`
			FROM
				`#__usergroups`;
			")->execute();
			$usergroups = $this->_db->setQuery("SELECT `title`,`id` FROM `#__j2xml_usergroups`")->loadAssocList('title','id');
		}

		function import($xml, $params)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$app = JFactory::getApplication('administrator');
			//gc_enable(); // Enable Garbage Collector

			if (strtoupper($xml->getName()) == 'J2XML')
			{
				if(!isset($xml['version']))
				{
					JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
					return false;
				}
				else
				{
					jimport('eshiol.j2xml.version');

					$xmlVersion = $xml['version'];
					if ($xmlVersion == '1.5.6')
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_J2XML15', $xmlVersion), JLOG::ERROR, 'lib_j2xml'));
						return false;
					}
					elseif (J2XMLVersion::docversion_compare($xmlVersion) == 1)
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion), JLOG::ERROR, 'lib_j2xml'));
						return false;
					}
				}
			}
			elseif (strtoupper($xml->getName()) == 'RSS')
			{
				$namespaces = $xml->getNamespaces(true);
				if (isset($namespaces['wp']))
				{
					if ($generator = $xml->xpath('/rss/channel/generator'))
					{
						if (preg_match("/http:\/\/wordpress.(org|com)\//", (string)$generator[0]) != false)
						{
							$xml->registerXPathNamespace('wp', $namespaces['wp']);
							if (!($wp_version = $xml->xpath('/rss/channel/wp:wxr_version')))
							{
								JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
								return false;
							}
							elseif ($wp_version[0] == '1.2')
							{
								JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_WP'), JLOG::ERROR, 'lib_j2xml'));
								return false;
							}
							elseif ($wp_version[0] == '1.1')
							{
								JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_J2XMLWP'), JLOG::ERROR, 'lib_j2xml'));
								return false;
							}
							else
							{
								JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
								return false;
							}
						}
						else
						{
							JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
							return false;
						}
					}
					else
					{
						JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
						return false;
					}
				}
				else
				{
					JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
					return false;
				}
			}
			elseif (strtoupper($xml->getName()) == 'HTML')
			{
				JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_J2XMLHTML'), JLOG::ERROR, 'lib_j2xml'));
				return false;
			}
			else
			{
				JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLOG::ERROR, 'lib_j2xml'));
				return false;
			}

			$params['version'] = $xmlVersion;
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
				$this->_db->setQuery($query);
				$maxid = $this->_db->loadResult();

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

					$this->_user_id = $data['id'];
					unset($data['id']);
					$query = 'SELECT id'
						. ' FROM #__users'
						// . ' WHERE'. (($keep_user_id == 1)
						// ? ' id = '.$this->_user_id
						// : ' username = '.$this->_db->q($data['username'])
						// )
						. ' WHERE username = '.$this->_db->q($data['username'])
						;
					$this->_db->setQuery($query);
					$data['id'] = $this->_db->loadResult();
					if (!$data['id'] || ($import_users == 2))
					{
						$this->_user = new UsersModelUser();
						$result = $this->_user->save($data);

						$this->_db->setQuery('SELECT id FROM #__users WHERE username = '.$this->_db->q($data['username']));
						if ($id = $this->_db->loadResult())
						{

							if ($error = $this->_user->getError())
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
								$this->_db  = JFactory::getDbo();
								$query = $this->_db->getQuery(true)
									->update('#__users')
									->set($this->_db->qn('password') . ' = ' . $this->_db->q($data['password_crypted']))
									->where($this->_db->qn('id') . ' = ' . $id)
									;
								$this->_db->setQuery($query);
								$this->_db->execute();

								if ($this->_user_id && !$data['id'] && ($keep_user_id == 1))
								{
									$id = $this->_user->getState('user.id');
									$query = "UPDATE #__users SET id = {$this->_user_id} WHERE id = {$id}";
									$this->_db->setQuery($query);
									$this->_db->query();
									$query = "UPDATE #__user_usergroup_map SET user_id={$this->_user_id} WHERE user_id={$id}";
									$this->_db->setQuery($query);
									$this->_db->query();
									if ($this->_user_id >= $autoincrement)
									{
										$autoincrement = $this->_user_id + 1;
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
				if ($autoincrement > $maxid)
				{
					$query = "ALTER TABLE #__users AUTO_INCREMENT = {$autoincrement}";
					$this->_db->setQuery($query);
					$this->_db->query();
				}
			}

			// import view levels
			foreach($xml->xpath("//j2xml/viewlevel[not(title = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);

				$id = $data['id'];
				$query = 'SELECT id, title'
					. ' FROM #__viewlevels'
					. ' WHERE title = '.$this->_db->q($data['title'])
					;
				$this->_db->setQuery($query);
				$viewlevel = $this->_db->loadObject();
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
						{
							$rules_id[] = $v;
						}
						unset($data['ruleslist']);
					}
					for($i = 0; $i < count($rules_id); $i++)
					{
						$usergroup = $rules_id[$i];
						if (isset($usergroups[$usergroup]))
						{
							$rules_id[$i] = $usergroups[$usergroup];
						}
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

			JLog::add(new JLogEntry('*** Importing tags... ***', JLOG::DEBUG, 'lib_j2xml'));
			foreach($xml->xpath("//j2xml/tag") as $record)
			{
				$this->prepareData($record, $data, $params);

				JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
				$data['id'] = 0;

				$path = $data['path'];
				$i = strrpos($path, '/');
				if ($i === false) 
				{
					$data['parent_id'] = 1;
				} 
				else 
				{
					$parent_path = substr($path, 0, $i);
					if (!isset($parent_ids[$parent_path]))
					{
						$query = 'SELECT id'
							. ' FROM #__tags'
							. ' WHERE path = '. $this->_db->q($parent_path)
							;
						$this->_db->setQuery($query);
						$parent_ids[$parent_path] = $this->_db->loadResult();
					}
					$data['parent_id'] = $parent_ids[$parent_path];
				}
				$table = JTable::getInstance('Tag', 'TagsTable');
				$table->setLocation($data['parent_id'], 'last-child');
				//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));
				$table->save($data);

				JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_TAG_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
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
					if ($i === false) 
					{
						$data['parent_id'] = 1;
					} 
					else 
					{
						$data['parent_id'] = self::getCategoryId(substr($path, 0, $i), $data['extension']);
					}

					if ($data['parent_id'] === false)
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
						JLog::add(new JLogEntry(JText::_('JLIB_DATABASE_ERROR_INVALID_PARENT_ID'), JLOG::ERROR, 'lib_j2xml'));
					}
					else
					{
						$query = 'SELECT id, title'
							. ' FROM #__categories'
							. ' WHERE extension = '. $this->_db->q($data['extension'])
							. ' AND'. ((($keep_id == 1) && ($id > 1)) ? ' id = '.$id : ' path = '.$this->_db->q($path))
							;
						JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
						$this->_db->setQuery($query);
						$category = $this->_db->loadObject();

						if (!$category || ($import_categories == 2))
						{
							$table = JTable::getInstance('category');
							JLog::add(new JLogEntry('import new category '.$path, JLOG::DEBUG, 'lib_j2xml'));

							if (!$category && ($keep_id == 1))
							{
								$query = 'SELECT id, title'
									. ' FROM #__categories'
									. ' WHERE path = '.$this->_db->q($path)
									. ' AND extension = '. $this->_db->q($data['extension'])
									;
								JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
								$this->_db->setQuery($query);
								$category = $this->_db->loadObject();
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
									$data['modified_user_id'] = $this->_user_id;
									$data['version'] = $table->version + 1;
								}
								else // save default values
								{
									$data['created'] = $now;
									$data['created_user_id'] = $this->_user_id;
									$data['created_by_alias'] = null;
									$data['modified'] = $this->_nullDate;
									$data['modified_user_id'] = null;
									$data['version'] = 1;
								}
								*/
							}

							//JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));

							$table->bind($data);

							$tags = array();
							if (isset($data['tag']))
							{
								$tags[] = $data['tag'];
							}
							elseif (isset($data['taglist']))
							{
								foreach ($data['taglist'] as $v)
								{
									$tags[] = $v;
								}
							}
							$table->newTags = eshHelperTags::convertPathsToIds($tags);

							// Trigger the onContentBeforeSave event.
							// $result = $dispatcher->trigger('onContentBeforeSave', array($this->_option.'.category', &$table, $isNew));
							// if (!in_array(false, $result, true))

							if ($table->store())
							{
								if (!$category && ($keep_id == 1) && ($id > 1))
								{
									try
									{
										$query = "UPDATE #__categories SET `id` = {$id} WHERE `id` = {$table->id}";
										$this->_db->setQuery($query);
										$this->_db->query();
										$table->id = $id;

										$query = "UPDATE #__assets SET `name` = '{$data['extension']}.category.{$id}' WHERE `id` = {$table->asset_id}";
										$this->_db->setQuery($query);
										$this->_db->query();

										$query = "SELECT max(`id`)+1 from #__categories";
										$this->_db->setQuery($query);
										$maxid = $this->_db->loadResult();

										$query = "ALTER TABLE #__categories AUTO_INCREMENT = {$maxid}";
										$this->_db->setQuery($query);
										$this->_db->query();
									}
									catch(Exception $ex)
									{
										JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title), JLOG::WARNING, 'lib_j2xml'));
									}
								}
								// Rebuild the tree path.
								$table->rebuildPath();

								if ($keep_id && ($id > 0) && ($id != $table->id))
								{
									JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title, $id, $table->id), JLOG::WARNING, 'lib_j2xml'));
								}
								else
								{
									JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
								}
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
			}

			if ($xml->xpath("//j2xml/field[not(name = '')]"))
			{
				$this->_fields($xml, $params);
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

			$this->_users_id = array();
			/*
			$query = "SELECT * FROM #__users WHERE id = 42";
			$this->_db->setQuery($query);
			$this->_user = $this->_db->loadObject();
			if ($this->_user)
				$this->_users_id['admin'] = 42;
			else
				$this->_users_id['admin'] = 62;
			$this->_users_id[0] = 0;
			*/

			if ($keep_frontpage)
			{
				$query = 'SELECT max(ordering)'
					. ' FROM #__content_frontpage'
					;
				$this->_db->setQuery($query);
				$frontpage = (int)$this->_db->loadResult();
			}

			$dispatcher = JDispatcher::getInstance();
			JPluginHelper::importPlugin('content');

			JLog::add(new JLogEntry('*** Importing articles... ***', JLOG::DEBUG, 'lib_j2xml'));

			$query = 'SELECT id, path'
				. ' FROM #__categories'
				. ' WHERE id = '.$params->get('category', 9)
				. ' AND extension = '. $this->_db->q('com_content')
				;
			$this->_db->setQuery($query);

			if ($default_cat = $this->_db->loadObject())
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
								. ' WHERE path = '.$this->_db->q($data['catid'])
								. ' AND extension = '.$this->_db->q('com_content')
								;
							$this->_db->setQuery($query);
							$category_id = (int)$this->_db->loadResult();
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

					if (($keep_id == 1) && ($id > 0))
					{
						$query = 'SELECT id, title'
							. ' FROM #__content'
							. ' WHERE id = '.$id
							;
					}
					else
					{
						$query = 'SELECT #__content.id, #__content.title'
							. ' FROM #__content LEFT JOIN #__categories'
							. ' ON #__content.catid = #__categories.id'
							. ' WHERE #__categories.path = '. $this->_db->q($category_path)
							. ' AND #__content.alias = '. $this->_db->q($alias)
							;
					}
					JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
					$this->_db->setQuery($query);
					$content = $this->_db->loadObject();

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
							if (isset($data['created_by']))
							{
								if (isset($this->_users_id[$data['created_by']]))
								{
									$data['created_by'] = $this->_users_id[$data['created_by']];
								}
								else
								{
									$query = 'SELECT id'
										. ' FROM #__users'
										. ' WHERE username = '. $this->_db->q($data['created_by'])
										;
									$this->_db->setQuery($query);
									$this->_userid = (int)$this->_db->loadResult();
									if ($this->_userid > 0)
									{
										$this->_users_id[$data['created_by']] = $this->_userid;
										$data['created_by'] = $this->_userid;
									}
									else
									{
										$data['created_by'] = $this->_user_id;
									}
								}
							}
							else
							{
								$data['created_by'] = $this->_user_id;
							}
							if (isset($data['modified_by']))
							{
								if (isset($this->_users_id[$data['modified_by']]))
									$data['modified_by'] = $this->_users_id[$data['modified_by']];
								else
								{
									$query = 'SELECT id'
										. ' FROM #__users'
										. ' WHERE username = '. $this->_db->q($data['modified_by'])
										;
									$this->_db->setQuery($query);
									$this->_userid = (int)$this->_db->loadResult();
									if ($this->_userid > 0)
									{
										$this->_users_id[$data['modified_by']] = $this->_userid;
										$data['modified_by'] = $this->_userid;
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
							$data['created_by'] = $this->_user_id;
							$data['created_by_alias'] = null;
							$data['modified'] = $this->_nullDate;
							$data['modified_by'] = null;
							$data['version'] = 1;
						}

						if (!$keep_frontpage)
						{
							$data['featured'] = 0;
						}
						elseif (isset($data['featured']) && ($data['featured'] > 0))
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
						if ($params->get('backlink', '0'))
						{
							$backlink = array(
								'link' => JText::_('LIB_J2XML_BACKLINK_LINK'),
								'text' => JText::_('LIB_J2XML_BACKLINK_TEXT'),
								'target' => '_blank'
							);
							if (!isset($data['urls']))
							{
								$urls = new stdClass();
								$urls->urla = $backlink['link'];
								$urls->urlatext = $backlink['text'];
								$urls->targeta = $backlink['target'];
							}
							else
							{
								$urls = json_decode($data['urls']);
								if (!isset($urls->urla) || ($urls->urla == ''))
								{
									$urls = new stdClass();
									$urls->urla = $backlink['link'];
									$urls->urlatext = $backlink['text'];
									$urls->targeta = $backlink['target'];
								}
								elseif ($urls->urlatext == $backlink['text'])
								{
									// do nothing
								}
								elseif (!isset($urls->urlb) || ($urls->urlb == ''))
								{
									$urls->urlb = $backlink['link'];
									$urls->urlbtext = $backlink['text'];
									$urls->targetb = $backlink['target'];
								}
								elseif ($urls->urlbtext == $backlink['text'])
								{
									// do nothing
								}
								elseif (!isset($urls->urlc) || ($urls->urlc == ''))
								{
									$urls->urlc = $backlink['link'];
									$urls->urlctext = $backlink['text'];
									$urls->targetc = $backlink['target'];
								}
							}
							$data['urls'] = json_encode($urls);
						}

						if ($params->get('linksourcefile', '1') && ($filename = $params->get('filename')))
						{
							JLog::add(new JLogEntry($filename, JLOG::DEBUG, 'lib_j2xml'));
							if (preg_match("|^(https?:)?\/\/|i", $filename))
							{
								if (!isset($data['urls']))
								{
									$urls = new stdClass();
									$urls->urla = $filename;
									$urls->urlatext = $data['title'];
									$urls->targeta = '_blank';
								}
								else
								{
									$urls = json_decode($data['urls']);
									JLog::add(new JLogEntry(__LINE__.' '.print_r($urls, true), JLOG::DEBUG, 'lib_j2xml'));
									if (!isset($urls->urla) || ($urls->urla == ''))
									{
										$urls->urla = $filename;
										$urls->urlatext = $data['title'];
										$urls->targeta = '_blank';
									}
									elseif (!isset($urls->urlb) || ($urls->urlb == ''))
									{
										$urls->urlb = $filename;
										$urls->urlbtext = $data['title'];
										$urls->targetb = '_blank';
									}
									elseif (!isset($urls->urlc) || ($urls->urlc == ''))
									{
										$urls->urlc = $filename;
										$urls->urlctext = $data['title'];
										$urls->targetc = '_blank';
									}
								}
								$data['urls'] = json_encode($urls);
								JLog::add(new JLogEntry(__LINE__.' '.$data['urls'], JLOG::DEBUG, 'lib_j2xml'));
							}
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
						$result = $dispatcher->trigger('onContentBeforeSave', array('com_content.article', &$table, $isNew));

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
								if (!$content && ($keep_id == 1) && ($id > 0))
								{
									$query = "UPDATE #__content SET `id` = {$id} WHERE `id` = {$table->id}";
									$this->_db->setQuery($query);
									$this->_db->query();
									$table->id = $id;
									$query = "UPDATE #__assets SET `name` = 'com_content.article.{$id}' WHERE `id` = {$table->asset_id}";
									$this->_db->setQuery($query);
									$this->_db->query();

									$query = "SELECT max(`id`)+1 from #__content";
									$this->_db->setQuery($query);
									$maxid = $this->_db->loadResult();

									$query = "ALTER TABLE #__content AUTO_INCREMENT = {$maxid}";
									$this->_db->setQuery($query);
									$this->_db->query();
								}

								if ($keep_frontpage)
								{
									if (!isset($data['featured']))
									{
										$query = "DELETE FROM #__content_frontpage WHERE content_id = ".$table->id;
									}
									elseif ($data['featured'] == 0)
									{
										$query = "DELETE FROM #__content_frontpage WHERE content_id = ".$table->id;
									}
									elseif($keep_id)
									{
										$query =
										' INSERT IGNORE INTO `#__content_frontpage`'
												. ' SET content_id = '.$table->id.','
														. '     ordering = '.$data['ordering'];
									}
									else
									{
										$frontpage++;
										$query =
										' INSERT IGNORE INTO `#__content_frontpage`'
												. ' SET content_id = '.$table->id.','
														. '     ordering = '.$frontpage;
									}
									$this->_db->setQuery($query);
									$this->_db->query();
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
												$this->_db->insertObject('#__content_rating', $rating);
											} catch (Exception $e) {
												$this->_db->updateObject('#__content_rating', $rating, 'content_id');
											}
										}
									else
									{
										$query = "DELETE FROM `#__content_rating` WHERE `content_id`=".$table->id;
										$this->_db->setQuery($query);
										$this->_db->query();
									}
								}
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));

								// Trigger the onContentAfterSave event.
								$dispatcher->trigger('onContentAfterSave', array('com_content.article', &$table, $isNew, $data));
							}
							else
							{
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'].' (id='.$id.')'), JLOG::ERROR, 'lib_j2xml'));
								if ($data['catid'])
								{
									JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
								}
								else
								{
									JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_FOUND', $catid), JLOG::ERROR, 'lib_j2xml'));
								}
							}
							// Undefined currentAssetId fix
							if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt'))
								error_reporting($error_level);
						}
					}
				}
			}
			else
			{
				JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_DEFAULT_CATEGORY_NOT_FOUND'), JLOG::DEBUG, 'lib_j2xml'));
			}

			/*
			 * Import Weblinks
			 */
			// Check if component is installed
			$this->_db->setQuery("SELECT enabled FROM #__extensions WHERE name = 'com_weblinks'");
			$is_enabled = $this->_db->loadResult();
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
					if (J2XMLVersion::docversion_compare($version) == -1)
					{
						$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');
						$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
					}
					$query = 'SELECT id, title'
						. ' FROM #__weblinks'
						. ' WHERE alias = '. $this->_db->q($alias)
						;
					$this->_db->setQuery($query);
					$weblink = $this->_db->loadObject();

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
								. ' WHERE path = '. $this->_db->q($data['catid'])
								. ' AND extension = '. $this->_db->q('com_weblinks')
								//						. ' AND level = 1'
								;
							JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
							$this->_db->setQuery($query);
							$category_id = (int)$this->_db->loadResult();
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
		$this->_db->setQuery("SELECT enabled FROM #__extensions WHERE name = 'com_buttons'");
		$is_enabled = $this->_db->loadResult();
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
				if (J2XMLVersion::docversion_compare($version) == -1)
				{
					$data['title'] = html_entity_decode($data['title'], ENT_QUOTES, 'UTF-8');
					$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
				}
				$query = 'SELECT id, title'
					. ' FROM #__buttons'
					. ' WHERE alias = '. $this->_db->q($alias)
					;
				$this->_db->setQuery($query);
				$button = $this->_db->loadObject();

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
							. ' WHERE alias = '. $this->_db->q($data['catid'])
							. ' AND extension = '. $this->_db->q('com_buttons')
							//						. ' AND level = 1'
							;
						$this->_db->setQuery($query);
						$category_id = (int)$this->_db->loadResult();
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
					if (!JFolder::exists($folder)) 
					{
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
					{
						$data[trim($key)] = trim($value);
					}
					else
					{
						foreach ($value->children() as $v)
						{
							$data[trim($key)][] = trim($v);
						}
					}
				}
				$id = $data['id'];
				$alias = $data['alias'];
				if (version_compare($version, '17.7.0') == -1)
				{
					$data['name'] = html_entity_decode($data['name'], ENT_QUOTES, 'UTF-8');
				}
				$query = 'SELECT id, alias'
					. ' FROM #__contact_details'
					. ' WHERE'. (($keep_user_id == 1)
					? ' id = '.$id
					: ' alias = '.$this->_db->q($alias)
					);
					$this->_db->setQuery($query);
					$contact = $this->_db->loadObject();
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
								. ' WHERE path = '. $this->_db->q($data['catid'])
								. ' AND extension = '. $this->_db->q('com_contact')
								// . ' AND level = 1'
								;
							JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
							$this->_db->setQuery($query);
							$category_id = (int)$this->_db->loadResult();
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
								$this->_db->setQuery($query);
								$this->_db->query();
								$table->id = $id;

								$query = "SELECT max(`id`)+1 from #__contact_details";
								$this->_db->setQuery($query);
								$maxid = $this->_db->loadResult();

								$query = "ALTER TABLE #__contact_details AUTO_INCREMENT = {$maxid}";
								$this->_db->setQuery($query);
								$this->_db->query();
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
					$contacts_id[$alias] = $this->_user->id;
					$contacts_title[$alias] = $this->_user->name;
				}
			}
		}

			if ($xml->xpath("//j2xml/menutype[not(title = '')]"))
			{
				$this->_menus($xml, $params);
			}

			if ($xml->xpath("//j2xml/module[not(title = '')]"))
			{
				$this->_modules($xml, $params);
			}

			//gc_disable(); // Disable Garbage Collector

			JPluginHelper::importPlugin('j2xml');
			$dispatcher = JDispatcher::getInstance();
			// Trigger the onAfterImport event.
			$dispatcher->trigger('onAfterImport', array('com_j2xml.import', &$xml, $params));
		}

		/**
		 * importing menus
		 *
		 * @param SimpleXMLElement $xml
		 * @param Registry $params
		 */
		private function _menus($xml, $params)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$import_menus = $params->get('import_menus', '1');
			foreach($xml->xpath("//j2xml/menutype[not(title = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);

				$query = 'SELECT id, title FROM #__menu_types WHERE menutype = '. $this->_db->q( $data['menutype']);
				$this->_db->setQuery($query);
				$menutype = $this->_db->loadObject();

				if (!$menutype || ($import_menus == 2))
				{
					$table = JTable::getInstance('MenuType');

					if (!$menutype)
					{ // new menutype
						$data['id'] = null;
					}
					else // menutype already exists
					{
						$data['id'] = $menutype->id;
						$table->load($data['id']);
					}

					// Trigger the onContentBeforeSave event.
					$table->bind($data);
					if ($table->store())
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_MENUTYPE_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
						// Trigger the onContentAfterSave event.
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
			}

			foreach($xml->xpath("//j2xml/menu[not(title = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);

				$query = 'SELECT id, title FROM #__menu WHERE path = '. $this->_db->q( $data['path']);
				$this->_db->setQuery($query);
				$menu = $this->_db->loadObject();

				if (!$menu || ($import_menus == 2))
				{
					$table = JTable::getInstance('Menu');

					if (!$menu)
					{ // new menu
						$data['id'] = null;
					}
					else // menu already exists
					{
						$data['id'] = $menu->id;
						$table->load($data['id']);
					}

					$this->_db->setQuery(
							$this->_db->getQuery(true)
							->select($this->_db->qn('extension_id'))
							->from($this->_db->qn('#__extensions'))
							->where($this->_db->qn('type').' = '.$this->_db->q('component'))
							->where($this->_db->qn('element').' = '.$this->_db->q($data['component_id']))
							);
					$component = $this->_db->loadResult();

					if ($component)
					{
						$data['component_id'] = $component;

						$args = array();
						parse_str(parse_url($data['link'], PHP_URL_QUERY), $args);
						if (isset($args['option']) && ($args['option'] == 'com_content'))
						{
							if (isset($args['view']) && ($args['view'] == 'article'))
							{
								$args['id'] = $this->getArticleId($data['article_id']);
								$data['link'] = 'index.php?'.http_build_query($args);
							}
						}

						// Trigger the onContentBeforeSave event.
						$table->bind($data);
						if ($table->store())
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_MENU_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
							// Trigger the onContentAfterSave event.
						}
						else
						{
							JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_MENU_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
							JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
						}
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_ERROR_COMPONENT_NOT_FOUND', $data['component_id']), JLOG::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
			}
		}

		/**
		 * importing modules
		 *
		 * @param SimpleXMLElement $xml
		 * @param Registry $params
		 */
		private function _modules($xml, $params)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$import_modules = $params->get('import_modules', '1');
			foreach($xml->xpath("//j2xml/module[not(title = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);

				$db = JFactory::getDbo();

				/* import module */
				$module =
				$db->setQuery(
						$db->getQuery(true)
						->select($db->qn('id'))
						->select($db->qn('title'))
						->from($db->qn('#__modules'))
						->where($db->qn('module').' = '.$db->q($data['module']))
						->where($db->qn('title').' = '.$db->q($data['title']))
						)->loadObject();

						if (!$module || ($import_modules == 2))
						{
							$table = JTable::getInstance('Module');

							if (!$module)
							{ // new menutype
								$data['id'] = null;
							}
							else // module already exists
							{
								$data['id'] = $module->id;
								$table->load($data['id']);
							}

							// Trigger the onContentBeforeSave event.
							$table->bind($data);
							if ($table->store())
							{
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_MODULE_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
								// Trigger the onContentAfterSave event.
							}
							else
							{
								JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_MODULE_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
								JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
							}
							$table = null;
						}
			}
		}

		static function clean()
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$this->_db = JFactory::getDBO();

			$this->_db->setQuery('TRUNCATE `#__contentitem_tag_map`')->execute();
			$this->_db->setQuery('TRUNCATE `#__tags`')->execute();
			$this->_db->setQuery("INSERT INTO `#__tags` (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `title`, `alias`, `note`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`, `metadesc`, `metakey`, `metadata`, `created_user_id`, `created_time`, `created_by_alias`, `modified_user_id`, `modified_time`, `images`, `urls`, `hits`, `language`, `version`, `publish_up`, `publish_down`) VALUES (1, 0, 0, 1, 0, '', 'ROOT', 'root', '', '', 1, 0, '0000-00-00 00:00:00', 1, '', '', '', '', 0, '2011-01-01 00:00:01', '', 0, '0000-00-00 00:00:00', '', '', 0, '*', 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00')")->execute();

			// contact
			$this->_db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_contact.contact')")->execute();
			$this->_db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_contact.contact.%'")->execute();
			$this->_db->setQuery("TRUNCATE `#__contact_details`")->execute();
			$this->_db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_contact.category')")->execute();
			$this->_db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_contact.category.%' AND `Title` <> 'Uncategorised'")->execute();
			$this->_db->setQuery("DELETE FROM `#__categories` WHERE `extension` = 'com_contact' AND `Title` <> 'Uncategorised'")->execute();
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_CONTACTS_CLEANED'), JLOG::NOTICE, 'lib_j2xml'));

			// content
			$this->_db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_content.article')")->execute();
			$this->_db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_content.article.%'")->execute();
			$this->_db->setQuery("TRUNCATE `#__content`")->execute();
			$this->_db->setQuery("TRUNCATE `#__content_frontpage`")->execute();
			$this->_db->setQuery("TRUNCATE `#__content_rating`")->execute();
			$this->_db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_content.category')")->execute();
			$this->_db->setQuery("DELETE FROM `#__ucm_content` WHERE `core_type_alias`='com_content.article'")->execute();
			$this->_db->setQuery("DELETE FROM `#__assets` WHERE `name` LIKE 'com_content.category.%' AND `Title` <> 'Uncategorised'")->execute();
			$this->_db->setQuery("DELETE FROM `#__categories` WHERE `extension` = 'com_content' AND `Title` <> 'Uncategorised'")->execute();
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_CONTENT_CLEANED'), JLOG::NOTICE, 'lib_j2xml'));

			// users
			$this->_db->setQuery("DELETE FROM `#__ucm_history` WHERE `ucm_type_id` IN (SELECT `type_id` FROM `#__content_types` WHERE `type_alias` = 'com_users.user')")->execute();
			$this->_db->setQuery("DELETE FROM `#__users` WHERE `id` NOT IN (SELECT user_id FROM `#__user_usergroup_map` WHERE group_id = 8)")->execute();
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_USERS_CLEANED'), JLOG::NOTICE, 'lib_j2xml'));

			// viewlevels
			$this->_db->setQuery("DELETE FROM `#__viewlevels` WHERE `id` > 6")->execute();
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_VIEWLEVELS_CLEANED'), JLOG::NOTICE, 'lib_j2xml'));

			// usergroups
			$this->_db->setQuery("DELETE FROM `#__usergroups` WHERE `id` > 9")->execute();
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_USERGROUPS_CLEANED'), JLOG::NOTICE, 'lib_j2xml'));
			/*
			 JPluginHelper::importPlugin('j2xml');
			 $dispatcher = JDispatcher::getInstance();
			 // Trigger the onAfterImport event.
			 $dispatcher->trigger('onClean', array('com_j2xml.clean', &$xml, $params));
			 */
		}

		function getArticleId($path)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$i = strrpos($path, '/');
			$query =
			$this->_db->getQuery(true)
			->select($this->_db->qn('c.id'))
			->from($this->_db->qn('#__content', 'c'))
			->join('INNER', $this->_db->qn('#__categories', 'cc').' ON '.$this->_db->qn('c.catid').'='.$this->_db->qn('cc.id'))
			->where($this->_db->qn('cc.extension').'='.$this->_db->q('com_content'))
			->where($this->_db->qn('c.alias').'='.$this->_db->q(substr($path, $i + 1)))
			->where($this->_db->qn('cc.path').'='.$this->_db->q(substr($path, 0, $i)))
			;
			JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
			$this->_db->setQuery($query);
			$article_id = $this->_db->loadResult();
			JLog::add(new JLogEntry($path.' -> '.$article_id, JLOG::DEBUG, 'lib_j2xml'));

			return $article_id;
		}

		function getUserId($username, $default_user_id)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$query =
			$this->_db->getQuery(true)
			->select($this->_db->qn('id'))
			->from($this->_db->qn('#__users'))
			->where($this->_db->qn('username').'='.$this->_db->q($username))
			;
			JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
			$this->_db->setQuery($query);
			if (!($this->_user_id = $this->_db->loadResult()))
			{
				$this->_user_id = $default_user_id;
			}
			JLog::add(new JLogEntry($username.' -> '.$this->_user_id, JLOG::DEBUG, 'lib_j2xml'));

			return $this->_user_id;
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
				$query =
				$this->_db->getQuery(true)
				->select($this->_db->qn('id'))
				->from($this->_db->qn('#__j2xml_usergroups'))
				->where($this->_db->qn('title').'='.$this->_db->q($usergroup))
				;
				JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
				$this->_db->setQuery($query);
				if (!($usergroup_id = $this->_db->loadResult()))
				{
					$groups = json_decode($usergroup);
					$g = array();
					$usergroup_id = 0;
					for ($j = 0; $j < count($groups); $j++)
					{
						$g[] = $groups[$j];
						$group = json_encode($g, JSON_NUMERIC_CHECK);
						if (isset($usergroups[$group]))
						{
							$usergroup_id = $usergroups[$group];
						}
						else // if import usergroup
						{
							$u = JTable::getInstance('Usergroup');
							$u->save(array('title'=>$groups[$j], 'parent_id'=>$usergroup_id));
							$usergroups[$group] = $usergroup_id = $u->id;
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

			if (is_numeric($access))
			{
				$access_id = $access;
			}
			else
			{
				$query =
					$this->_db->getQuery(true)
						->select($this->_db->qn('id'))
						->from($this->_db->qn('#__viewlevels'))
						->where($this->_db->qn('title').'='.$this->_db->q($access))
				;
				JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
				$this->_db->setQuery($query);
				$access_id = $this->_db->loadResult();
				if (!$access_id)
				{
					$access_id = 3;
				}
			}
			JLog::add(new JLogEntry($access.' -> '.$access_id, JLOG::DEBUG, 'lib_j2xml'));

			return $access_id;
		}

		function getCategoryId($category, $extension)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			if (is_numeric($category))
			{
				$category_id = $category;
			}
			else
			{
				$query =
					$this->_db->getQuery(true)
						->select($this->_db->qn('id'))
						->from($this->_db->qn('#__categories'))
						->where($this->_db->qn('path').'='.$this->_db->q($category))
						->where($this->_db->qn('extension').'='.$this->_db->q($extension))
				;
				JLog::add(new JLogEntry($query, JLOG::DEBUG, 'lib_j2xml'));
				$this->_db->setQuery($query);
				$category_id = $this->_db->loadResult();
				if (!$category_id)
				{
					$category_id = false;
				}
			}
			JLog::add(new JLogEntry($extension.'/'.$category.' -> '.$category_id, JLOG::DEBUG, 'lib_j2xml'));

			return $category_id;
		}

		public function prepareData(&$record, &$data, $params)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$data = array();
			foreach($record->children() as $key => $value)
			{
				JLog::add(new JLogEntry($key.' '.print_r($value, true), JLOG::DEBUG, 'lib_j2xml'));

				if (version_compare($params['version'], '17.7.0') == -1)
				{
					if (count($value->children()) === 0)
					{
						//$data[trim($key)] = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');
						$data[trim($key)] = html_entity_decode(preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($value)), ENT_QUOTES, 'UTF-8');
					}
					else
					{
						foreach ($value->children() as $k => $v)
						{
							//$data[trim($key)][$k] = html_entity_decode(trim($v), ENT_QUOTES, 'UTF-8');
							$data[trim($key)][$k] = html_entity_decode(preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($v)), ENT_QUOTES, 'UTF-8');
						}
					}
				}
				else
				{
					if (count($value->children()) === 0)
					{
						// $data[trim($key)] = trim($value);
						$data[trim($key)] = preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($value));
					}
					else
					{
						foreach ($value->children() as $k => $v)
						{
							// $data[trim($key)][$k] = trim($v);
							$data[trim($key)][$k] = preg_replace('/%u([0-9A-F]+)/', '&#x$1;', trim($v));
						}
					}
				}
			}
			$data['checked_out'] = 0;
			$data['checked_out_time'] = $this->_nullDate;
			if (isset($data['created_user_id']))
			{
				$data['modified_user_id'] = self::getUserId($data['created_user_id'], $this->_user_id);
			}
			if (isset($data['modified_user_id']))
			{
				$data['modified_user_id'] = self::getUserId($data['modified_user_id'], 0);
			}
			if (isset($data['access']))
			{
				$data['access'] = self::getAccessId($data['access']);
			}
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
			if (isset($data['field']))
			{
				$data['com_fields'] = $data['field'];
				unset($data['field']);
			}
			JLog::add(new JLogEntry(print_r($data, true), JLOG::DEBUG, 'lib_j2xml'));
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

		/**
		 * importing fields
		 *
		 * @param SimpleXMLElement $xml
		 * @param Registry $params
		 */
		private function _fields($xml, $params)
		{
			JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'lib_j2xml'));

			$import_fields = $params->get('import_fields', '1');
			foreach($xml->xpath("//j2xml/field[not(name = '')]") as $record)
			{
				$this->prepareData($record, $data, $params);

				$db = JFactory::getDbo();

				/* import field */
				$field =
					$db->setQuery(
						$db->getQuery(true)
							->select($db->qn('id'))
							->select($db->qn('name'))
							->from($db->qn('#__fields'))
							->where($db->qn('context').' = '.$db->q($data['context']))
							->where($db->qn('name').' = '.$db->q($data['name']))
					)->loadObject();

				if (!$field || ($import_fields == 2))
				{
					require_once JPATH_ADMINISTRATOR.'/components/com_fields/tables/field.php';
					$table = JTable::getInstance('Field', 'FieldsTable');

					if (!$field)
					{ // new field
						$data['id'] = null;
					}
					else // field already exists
					{
						$data['id'] = $field->id;
						$table->load($data['id']);
					}

					// Trigger the onContentBeforeSave event.
					$table->bind($data);
					if ($table->store())
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FIELD_IMPORTED', $table->title), JLOG::INFO, 'lib_j2xml'));
						// Trigger the onContentAfterSave event.
					}
					else
					{
						JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FIELD_NOT_IMPORTED', $data['title']), JLOG::ERROR, 'lib_j2xml'));
						JLog::add(new JLogEntry($table->getError(), JLOG::ERROR, 'lib_j2xml'));
					}
					$table = null;
				}
			}
		}
	}
