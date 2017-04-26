<?php
/**
 * @version		3.7.40 plugins/system/j2xml/j2xml.php
 * 
 * @package		J2XML
 * @subpackage	plg_system_j2xml
 * @since		1.5.2
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2017 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License 
 * or other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');
jimport('eshiol.core.send');
jimport('eshiol.core.standard2');

class plgSystemJ2XML extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param  object  $subject  The object to observe
	 * @param  array   $config   An array that holds the plugin configuration
	 */
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if ($this->params->get('debug') || defined('JDEBUG') && JDEBUG)
		{
			JLog::addLogger(array('text_file' => $this->params->get('log', 'eshiol.log.php'), 'extension' => 'plg_system_j2xml_file'), JLog::ALL, array('plg_system_j2xml'));
		}
		if (PHP_SAPI == 'cli')
		{
			JLog::addLogger(array('logger' => 'echo', 'extension' => 'plg_system_j2xml'), JLOG::ALL & ~JLOG::DEBUG, array('plg_system_j2xml'));
		}
		else
		{
			JLog::addLogger(array('logger' => (null !== $this->params->get('logger')) ?$this->params->get('logger') : 'messagequeue', 'extension' => 'plg_system_j2xml'), JLOG::ALL & ~JLOG::DEBUG, array('plg_system_j2xml'));
			if ($this->params->get('phpconsole') && class_exists('JLogLoggerPhpconsole'))
			{
				JLog::addLogger(['logger' => 'phpconsole', 'extension' => 'plg_system_j2xml_phpconsole'],  JLOG::DEBUG, array('plg_system_j2xml'));
			}
		}
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_system_j2xml'));
	}

	/**
	 * Method is called by index.php and administrator/index.php
	 *
	 * @access	public
	 */
	public function onAfterDispatch()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_system_j2xml'));
		$app = JFactory::getApplication();
		if($app->getName() != 'administrator') {
			return true;
		}

		$enabled = JComponentHelper::getComponent('com_j2xml', true);
		if (!$enabled->enabled) 
		{
			return true; 
		}
		
		$jinput   = JFactory::getApplication()->input;
		$option = $jinput->get('option');
		$view = $jinput->get('view');
		$extension = $jinput->get('extension');

		if (($option == 'com_content') && (!$view || $view == 'articles' || $view == 'featured')
			|| ($option == 'com_users') && (!$view || $view == 'users')
			|| ($option == 'com_weblinks') && (!$view || $view == 'weblinks')
			|| ($option == 'com_categories') && ($extension == 'com_content') && (!$view || $view == 'categories')
			|| ($option == 'com_categories') && ($extension == 'com_buttons') && (!$view || $view == 'categories')
			|| ($option == 'com_contact') && (!$view || $view == 'contacts')
			|| ($option == 'com_menus') && (!$view || $view == 'menus')
			|| ($option == 'com_modules') && (!$view || $view == 'modules')
		) {
			$toolbar = JToolBar::getInstance('toolbar');
			$control = substr($option, 4);
			$toolbar->appendButton('Standard2', 'download', 'PLG_SYSTEM_J2XML_BUTTON_EXPORT', "j2xml.{$control}.export", true);
			$doc = JFactory::getDocument();
			//$doc->addStyleDeclaration(" .icon-32-waiting {background:url(../media/lib_eshiol/images/icon-32-waiting.gif) no-repeat; }");
			$doc->addScript("../media/lib_eshiol_core/js/encryption.js");
			$doc->addScript("../media/lib_eshiol_core/js/core.js");
			$websites = self::getWebsites();
			if ($n = count($websites))
			{
				for($i = 0; $i < $n; $i++)
					$websites[$i]->url = "index.php?option=com_j2xml&task={$control}.send&w_id=".$websites[$i]->id;
				$toolbar->appendButton('Send', 'out', 'PLG_SYSTEM_J2XML_BUTTON_SEND', $websites, true);
			}
		}

		// Trigger the onAfterDispatch event.
//		JPluginHelper::importPlugin('j2xml');
//		JFactory::getApplication()->triggerEvent('onLoadJS');

		return true;
	}

	/**
	 * Method to return a list of all websites
	 *
	 * @return  array  List of websites (empty array if none).
	 */
	private static function getWebsites()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_system_j2xml'));
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('id, title')
			->from('#__j2xml_websites')
			->where('state = 1')
			;
		$db->setQuery($query);
		try {
			$websites = $db->loadObjectList();
		} catch (Exception $e) {
			$websites = null;
		}
		return $websites;
	}
}