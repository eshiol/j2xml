<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
 * @version		3.7.188
 * @since		2.5.85
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

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_j2xml/helpers/html');

if ((new \JVersion())->isCompatible('3.4'))
{
	JHtml::_('behavior.formvalidator');
}
else
{
	JHtml::_('behavior.formvalidation');
}
JHtml::_('formbehavior.chosen', 'select');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'website.cancel' || document.formvalidator.isValid(document.getElementById('website-form'))) {
			Joomla.submitform(task, document.getElementById('website-form'));
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_j2xml&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="website-form" class="form-validate">
	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', empty($this->item->id) ? JText::_('COM_J2XML_NEW_WEBSITE', true) : JText::_('COM_J2XML_NEW_WEBSITE', true)); ?>
		<div class="row-fluid">
			<div class="span9">
				<div class="form-vertical">
					<?php echo $this->form->renderField('remote_url'); ?>
					<?php echo $this->form->renderField('type'); ?>
					<?php echo $this->form->renderField('username'); ?>
					<?php echo $this->form->renderField('password'); ?>
					<?php echo $this->form->renderField('client_id'); ?>
					<?php echo $this->form->renderField('client_secret'); ?>
					<?php echo $this->form->renderField('redirect_uri'); ?>
					<?php echo $this->form->renderField('access_token'); ?>
					<?php echo $this->form->renderField('refresh_token'); ?>
				</div>
			</div>
			<div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
<?php echo J2XMLHelper::copyright(); ?>