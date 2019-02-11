/**
 * @package		eshiol Library
 * @subpackage	lib_eshiol_core
 * @version		19.2.33
 * @since		12.0.1
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * eshiol Library is free software. This version may have been modified 
 * pursuant to the GNU General Public License, and as distributed it includes 
 * or is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

if (typeof(eshiol) === 'undefined') {
	var eshiol = {};
}

eshiol.version = '19.2.33';

if (console) console.log('eshiol Library v'+eshiol.version);

Joomla.JText.strings['SUCCESS'] = 'Message';

/**
 * Render messages send via JSON
 *
 * @param	object	messages	JavaScript object containing the messages to render
 * @return	void
 */
eshiol.renderMessages = function(messages) {
	var container = document.id('system-message-container');

	Object.each(messages, function (item, type) {
		if (type == 'message')
			type = 'success';

		var div = $$('#system-message-container div.alert.alert-'+type);
		if (!div[0])
		{
			close = new Element('button', {
				'class': 'close',
				'data-dismiss': 'alert',
				'type': 'button',
				'html': '&times;'
			});
			close.inject(container);

			div = new Element('div', {
/*				id: 'system-message', */
				'class': 'alert alert-' + type
			});
			div.inject(container);

			var h4 = new Element('h4', {
				'class' : 'alert-heading',
				html: Joomla.JText._(type, type.charAt(0).toUpperCase() + type.slice(1))
			});
			h4.inject(div);
		}
		else
		{
			div = div[0];
		}

//		var divList = new Element('div');
		Array.each(item, function (item, index, object) {
			var p = new Element('p', {
				html: item
			});
//			p.inject(divList);
			p.inject(div);
		}, this);
//		divList.inject(div);
	}, this);
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
				dumped_text += eshiol.dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

eshiol.sendAjax = function(name, title, url) 
{
	text = $('toolbar-'+name).getElement('button').getElement('span').innerHTML;
	Joomla.removeMessages();
	url = Base64.decode(url);
	while (url.charCodeAt(url.length-1) == 0)
		url = url.substr(0,url.length-1);

	var n = 0;
	var tot = 0;
	var AJAX_QUEUE = [];

	for (var i = 0; $('cb'+i) != null; i++)
	{
		if ($('cb'+i).checked)
		{
			var x = new Request.JSON({
				'cb': $('cb'+i),
				url: url+'&cid='+$('cb'+i).value,
				method: 'post',
				onRequest: function() 
				{
				},
				onComplete: function(xhr, status, args)
				{
					if (n == AJAX_QUEUE.length)
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
					else
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
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
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
					else
					{
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
						AJAX_QUEUE[n].send();
					}
				},
				onFailure: function(r)
				{
					eshiol.renderMessages({'error':['Unable to connect the server: '+title]});
					n++;
					if (n == AJAX_QUEUE.length)
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
					else
					{
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
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
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
					else
					{
						$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
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
		$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... 0%';
		AJAX_QUEUE[0].send();
	}
}

eshiol.sendAjaxByDate = function(name, title, url) 
{
	d1 = $(name+'_begin').value;
	d2 = $(name+'_end').value;
	text = $('toolbar-'+name).getElement('button').getElement('span').innerHTML;
	Joomla.removeMessages();
	url = Base64.decode(url);
	while (url.charCodeAt(url.length-1) == 0)
		url = url.substr(0,url.length-1);

	var n = 0;
	var tot = 0;
	var AJAX_QUEUE = [];

	var start = new Date(d1);
	var end = new Date(d2);
	var iLen;

	while (start <= end)
	{
		year = start.getFullYear();

		month = "0"+String(start.getMonth()+1);
		iLen = month.length;
		month = month.substring(iLen, iLen - 2);

		day = "0"+String(start.getDate());
		iLen = day.length;
		day = day.substring(iLen, iLen - 2);

		jQuery('input[name="checkall-toggle"]').each(function () {
			this.checked = false;
		});

		for (var i = 0; $('cb'+i) != null; i++)
		{
			if ($('cb'+i).checked)
			{
				if (console) console.log(url+'&cid='+$('cb'+i).value+'&date='+(year+"-"+month+"-"+day));
				var x = new Request.JSON({
					'cb': $('cb'+i),
					url: url+'&cid='+$('cb'+i).value+'&date='+(year+"-"+month+"-"+day),
					method: 'post',
					onRequest: function() 
					{
					},
					onComplete: function(xhr, status, args)
					{
						if (n == AJAX_QUEUE.length)
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
						else
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
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
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
						else
						{
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
							AJAX_QUEUE[n].send();
						}
					},
					onFailure: function(r)
					{
						eshiol.renderMessages({'error':['Unable to connect the server: '+title]});
						n++;
						if (n == AJAX_QUEUE.length)
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
						else
						{
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
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
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text;
						else
						{
							$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... '+Math.floor(100*n/tot)+'%';
							AJAX_QUEUE[n].send();
						}
					}
				});
				AJAX_QUEUE.push(x);
				tot++;
			}
		}
		start.setDate(start.getDate() + 1);
	}

	if (AJAX_QUEUE.length)
	{
		n = 0;
		$('toolbar-'+name).getElement('button').getElement('span').innerHTML = text+'... 0%';
		AJAX_QUEUE[0].send();
	}
}

eshiol.XMLToString = function(xmlDom)
{
//	console.log("eshiol.XMLToString");
//	console.log(xmlDom);
	x =
		(typeof XMLSerializer!=="undefined")
        ? (new window.XMLSerializer()).serializeToString(xmlDom)
        : xmlDom.xml;
//    console.log(x);
	return x;
}   
