/**
 * @version		13.8.8 media/lib_eshiol_core/js/core.11.js
 * 
 * @package		eshiol Library
 * @subpackage	lib_eshiol
 * @since		12.0.1
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2012-2013 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * eshiol Library is free software. This version may have been modified 
 * pursuant to the GNU General Public License, and as distributed it includes 
 * or is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// Only define the eshiol namespace if not defined.			
if (typeof(eshiol) === 'undefined') {
	var eshiol = {};
}

/**
 * Render messages send via JSON
 *
 * @param	object	messages	JavaScript object containing the messages to render
 * @return	void
 */
eshiol.renderMessages = function(messages) {
	var container = document.id('system-message-container');
	var dl = $('system-message');
	if (!dl)
		dl = new Element('dl', {
			id: 'system-message',
			role: 'alert'
		});
	Object.each(messages, function (item, type) {
		var dt = $$('#system-message dt.'+type);
		if (!dt[0])
		{
			dt = new Element('dt', {
				'class': type,
				html: type
			});
			dt.inject(dl);
		}
		var dd = $$('#system-message dd.'+type);
		if (dd[0])
		{
			dd = dd[0];
			var list = $$('#system-message dd.'+type+' ul')[0];
			Array.each(item, function (item, index, object) {
				var li = new Element('li', {
					html: item
				});
				li.inject(list);
			}, this);
		}
		else
		{
			var dd = new Element('dd', {
				'class': type
			});
			dd.addClass('message');
			var list = new Element('ul');
			Array.each(item, function (item, index, object) {
				var li = new Element('li', {
					html: item
				});
				li.inject(list);
			}, this);
		}
		list.inject(dd);
		dd.inject(dl);
	}, this);
	dl.inject(container);
};		

eshiol.dump = function (arr,level) {
	var dumped_text = "";
	if(!level) level = 0;
	
	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";
	
	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];
			
			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

eshiol.sendAjax = function(name, id, title, url) 
{
	SqueezeBox.close();
	button = $('toolbar-'+name).getElement('a').innerHTML;
	$('toolbar-'+name).getElement('span').addClass('icon-32-waiting');
	img = $('toolbar-'+name).getElement('span').className;
	Joomla.removeMessages();
	url = Base64.decode(url);
	while (url.charCodeAt(url.length-1) == 0)
		url = url.substr(0,url.length-1);
	
	var n = 0;
	var tot = 0;
	var ok = 0;
	var AJAX_QUEUE = [];

	for (var i = 0; $('cb'+i) != null; i++)
	{
		if ($('cb'+i).checked)
		{
			var x = new Request.JSON({
				'cb': $('cb'+i),
				url: url+'&cid='+$('cb'+i).value+'&'+name+'_id='+id,
				method: 'post',
				onRequest: function() 
				{
				},
				onComplete: function(xhr, status, args)
				{
					if (n == AJAX_QUEUE.length)
						$('toolbar-'+name).getElement('a').innerHTML = button;
//						$('toolbar-'+name).getElement('span').removeClass('icon-32-waiting');
					else
						$('toolbar-'+name).getElement('a').innerHTML = '<span class=\''+img+'\'> </span>'+Math.floor(100*n/tot)+'%';
				},
				onError: function(text, r)
				{
					if (r.error && r.message)
					{
						alert(r.message);
					}
					if (r.messages)
					{
						eshiol.renderMessages(r.messages);
					}
					n++;
					if (n == AJAX_QUEUE.length)
						$('toolbar-'+name).getElement('a').innerHTML = button;
//						$('toolbar-'+name).getElement('span').removeClass('icon-32-waiting');					
					else
					{
						$('toolbar-'+name).getElement('a').innerHTML = '<span class=\''+img+'\'> </span>'+Math.floor(100*n/tot)+'%';
						AJAX_QUEUE[n].send();
					}
				},
				onFailure: function(r)
				{
					eshiol.renderMessages({'error':['Unable to connect the server: '+title]});
					n++;
					if (n == AJAX_QUEUE.length)
						$('toolbar-'+name).getElement('a').innerHTML = button;
//						$('toolbar-'+name).getElement('span').removeClass('icon-32-waiting');					
					else
					{
						$('toolbar-'+name).getElement('a').innerHTML = '<span class=\''+img+'\'> </span>'+Math.floor(100*n/tot)+'%';
						AJAX_QUEUE[n].send();
					}
				},
				onSuccess: function(r) 
				{
					this.options.cb.checked = false;
					if (r.error && r.message)
					{
						alert(r.message);
					}
					if (r.messages)
					{
						eshiol.renderMessages(r.messages);
					}
					n++;
					if (n == AJAX_QUEUE.length)
						$('toolbar-'+name).getElement('a').innerHTML = button;
//						$('toolbar-'+name).getElement('span').removeClass('icon-32-waiting');					
					else
					{
						$('toolbar-'+name).getElement('a').innerHTML = '<span class=\''+img+'\'> </span>'+Math.floor(100*n/tot)+'%';
						AJAX_QUEUE[n].send();
					}
				}
			});
			AJAX_QUEUE.push(x);
			tot++;
		}
	}
	if (AJAX_QUEUE.length)
	{
		n = 0;
		$('toolbar-'+name).getElement('a').innerHTML = '<span class=\''+img+'\'> </span>0%';
		AJAX_QUEUE[0].send();
	}	
}