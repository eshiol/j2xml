<?php
# 13.3.5


defined('JPATH_PLATFORM') or die;

jimport('eshiol.j2fb.facebook');

/**
 * Renders a Post2FB button
 *
 * @package     Joomla.Libraries
 * @subpackage  Toolbar
 * @since       3.0
 */
class JToolbarButtonJ2FBPost extends JToolbarButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'J2FBPost';

	static public $appid;
	static public $secret;
	
	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string   $type  Unused string.
	 * @param   string   $name  The name of the button icon class.
	 * @param   string   $text  Button text.
	 * @param   string   $task  Task associated with the button.
	 * @param   boolean  $list  True to allow lists
	 *
	 * @return  string  HTML string for the button
	 *
	 * @since   3.0
	 */
	public function fetchButton($type = 'J2FBPost', $name = '', $text = '', $task = '', $list = false)
	{
		$class = $this->fetchIconClass($name);
		if ($name == "apply" || $name == "new")
		{
			$btnClass = "btn btn-small btn-success";
			$iconWhite = "icon-white";
		}
		else
		{
			$btnClass = "btn btn-small";
			$iconWhite = "";
		}

		JFactory::getDocument()->addStyleDeclaration('div#toolbar div#toolbar-'.$name.' button.btn i.icon-'.$name.'-login::before {color: #2F96B4;content: "\"";}');
		JFactory::getDocument()->addStyleDeclaration('div#toolbar div#toolbar-'.$name.' button.btn i.icon-'.$name.'::before {color: #2F96B4;content: "&";}');
		JFactory::getDocument()->addStyleDeclaration('div#toolbar div#toolbar-'.$name.' button.btn i.icon-'.$name.'-waiting::before {color: #2F96B4;content: "j";}');
		
		// Create our Application instance
		$facebook = new Facebook(array(
				'appId'  => self::$appid,
				'secret' => self::$secret,
				'cookie' => true,
		));
		if ($fbuser = $facebook->getUser())
		{
			try 
			{
			 	$user_profile = $facebook->api('/me');
				//Get user pages details using Facebook Query Language (FQL)
				$fql_query = 'SELECT page_id, name, page_url FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid='.$fbuser.')';
				$postResults = $facebook->api(array( 'method' => 'fql.query', 'query' => $fql_query ));
			} catch (FacebookApiException $e) {
				JError::raiseWarning(1, $e->getMessage());
				return null;
	  		}
		}
		else
		{
			//Show login button for guest users
			$i18n_text = JText::_('Login');
			$doTask = $facebook->getLoginUrl(array('redirect_uri'=>JURI::current(),'scope'=>'publish_stream,read_stream,offline_access,manage_pages'));
			/* 
			 * publish_stream – allows the application to publish updates to Facebook on the user’s behalf
			 * read_stream – allows the application to read from the user’s News Feed
			 * offline_access – converts the access_token to one that doesn’t expire, thus letting the application make API calls anytime. Without this, the application’s access_token will expire after a few minutes, which isn’t ideal in this case
			 * manage_pages – lets the application access the user’s Facebook Pages. Since the application we’re building deals with Facebook Pages, we’ll need this as well.
			 */			
			$html = "<button class=\"btn btn-small\" onclick=\"location.href='$doTask';\">\n";
			$html .= "<i class=\"{$class}-login\">\n";
			$html .= "</i>\n";
			$html .= "$i18n_text\n";
			$html .= "</button>\n";
		}
		
		if ($fbuser && $postResults)
		{
			$i18n_text = JText::_($text);
			$doTask = $this->_getCommand($name, $task, $list);
			
			$doc = JFactory::getDocument();
			$doc->addScript("../media/lib_eshiol_core/js/encryption.js");
			$doc->addScript("../media/lib_eshiol_core/js/core.js");
			
			foreach ($postResults as $item) {
				$options[] = array('text'=>$item['name'], 'value'=>$item['page_id']);
			}
			array_unshift($options, JHTML::_('select.option',0,'- Select a page -'));

			$html = "<button data-toggle=\"modal\" data-target=\"#collapseModal$name\" class=\"" . $btnClass . "\">\n";
			$html .= "<i class=\"$class $iconWhite\">\n";
			$html .= "</i>\n";
			$html .= "$i18n_text\n";
			$html .= "</button>\n";
			$html .= "
<div class=\"modal hide fade\" id=\"collapseModal$name\">
	<div class=\"modal-header\">
		<button type=\"button\" class=\"close\" data-dismiss=\"modal\">x</button>
		<h3>Post to Facebook</h3>
	</div>
	<div class=\"modal-body\" style=\"min-height:250px\">
		<p>Post to facebook using www.eshiol.it app</p>
		<div class=\"control-group\">
			<div class=\"controls\">"
					.JHtml::_(
							'select.genericlist',
							$options,
							'fbpage',
							array(
									'list.attr' => 'class="inputbox" size="1"',
									'list.select' => 0
							)
					)."
			</div>
		</div>
	</div>
	<div class=\"modal-footer\">
		<button class=\"btn\" type=\"button\" onclick=\"document.id('fbpage').value=''\" data-dismiss=\"modal\">
			".JText::_('JCANCEL')."
		</button>
		<button class=\"btn btn-primary\" type=\"button\" onclick=\"$doTask\">
			$i18n_text
		</button>
	</div>
</div>
			";		
		}	
		return $html;
	}
	/**
	 * Get the button CSS Id
	 *
	 * @param   string   $type      Unused string.
	 * @param   string   $name      Name to be used as apart of the id
	 * @param   string   $text      Button text
	 * @param   string   $task      The task associated with the button
	 * @param   boolean  $list      True to allow use of lists
	 * @param   boolean  $hideMenu  True to hide the menu on click
	 *
	 * @return  string  Button CSS Id
	 *
	 * @since   3.0
	 */
	public function fetchId($type = 'Post2FB', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
	{
		return $this->_parent->getName() . '-' . $name;
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string   $name  The task name as seen by the user
	 * @param   string   $task  The task used by the application
	 * @param   boolean  $list  True is requires a list confirmation.
	 *
	 * @return  string   JavaScript command string
	 *
	 * @since   3.0
	 */
	protected function _getCommand($name, $task, $list)
	{
		JHtml::_('behavior.framework');
		$message = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
		$message = addslashes($message);

		$task = explode('.', $task);
		if (isset($task[2]))
			$task[1] = $task[1].'.'.$task[2];
		
		$url = "index.php?option=com_{$task[0]}&task={$task[1]}&format=json";

		$cmd = "if ($('fbpage').value == 0)
			alert('Please, first select a page');
		else 
		{
			eshiol.sendAjax('{$name}', $('fbpage').value, 'https://www.facebook.com/'+$('fbpage').options[$('fbpage').selectedIndex].text, '".base64_encode($url)."', '".JSession::getFormToken()."=1');
			jQuery('#collapseModal{$name}').modal('hide');
		}
		";

		return $cmd;
	}
}