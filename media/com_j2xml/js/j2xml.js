/**
 * @package     Joomla.Site
 * @subpackage  com_j2xml
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// Avoid `console` errors in browsers that lack a console.
(function (){
	var methods = [
		'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
		'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
		'profile', 'profileEnd', 'table', 'time', 'timeEnd', 'timeStamp',
		'trace', 'warn'
	];
	console = window.console = window.console || {};
	methods.forEach( function( method ){
		if ( !console[method] ){
			console[method] = function (){};
		}
	} );

	console.dump = function( object ){
		if( window.JSON && window.JSON.stringify ){
			console.log( JSON.stringify( object ) );
		}
		else {
			console.log( object );
		}
	};
}() );

if ( typeof( eshiol ) === 'undefined' ){
	eshiol = {};
}

if ( typeof( eshiol.j2xml ) === 'undefined' ){
	eshiol.j2xml = {};
}

if ( typeof( eshiol.j2xml.com_j2xml ) === 'undefined' ){
	eshiol.j2xml.com_j2xml = {};
}

eshiol.j2xml.com_j2xml.version = '__DEPLOY_VERSION__';

console.log( 'J2XML v' + eshiol.j2xml.com_j2xml.version );

eshiol.j2xml.importerModal = function(){
	console.log( 'eshiol.j2xml.importerModal' );

	jQuery( '#j2xmlImportModal iframe' ).contents().find( '#adminForm input[name^=jform], #adminForm select[name^=jform]' ).each( function( index ){
		var input = jQuery( this );
		jQuery( '<input>' ).attr( {
			type: 'hidden',
			id: input.attr( 'id' ),
			name: input.attr( 'name' ),
			value: input.val()
		} ).appendTo( '#adminForm' );
	} );
	window.top.setTimeout( 'window.parent.jQuery(\'#j2xmlImportModal\').modal(\'hide\')', 700 );

	xmlDoc = jQuery.parseXML( base64.decode( jQuery( '#j2xml_data' ).val() ) );
	$xml = jQuery( xmlDoc );
	root = $xml.find( ':root' )[0];

	$root = jQuery( root );
	header =
		'<?xml version="1.0" encoding="UTF-8" ?>'+"\n"+
		'<j2xml version="' + $root.attr( 'version') + '">' + "\n";
	footer = "\n</j2xml>";

	var nodes = [];

	$root.children( 'user' ).each( function( index ){
		console.log( 'user: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'tag' ).each( function( index ){
		console.log( 'tag: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'category' ).each( function( index ){
		console.log( 'category: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'fieldgroup' ).each( function( index ){
		console.log( 'fieldgroup: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );
	$root.children( 'field' ).each( function( index ){
		console.log( 'field: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'content' ).each( function( index ){
		console.log( 'content: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'menutype' ).each( function( index ){
		console.log( 'menutype: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'menu' ).each( function( index ){
		console.log( 'menu: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'module' ).each( function( index ){
		console.log( 'module: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children(  ).each( function( index ){
		if ( ['base', 'user', 'tag', 'category', 'content', 'fieldgroup', 'field', 'menutype', 'menu', 'module'].indexOf( this.nodeName ) == -1 ){
			console.log( 'other: ' + header + eshiol.XMLToString( this ) + footer );
			nodes.push( header + eshiol.XMLToString( this ) + footer );
		}
	} );

	eshiol.removeMessages();

	var options = {
		tot: nodes.length,
		info: 0,
		success: 0,
		warning: 0,
		error: 0
	}

	if ( typeof Joomla.getOptions !== 'undefined' ){
		var progressBarContainerClass = Joomla.getOptions( 'progressBarContainerClass', 'progress progress-striped active' );
		var progressBarClass = Joomla.getOptions( 'progressBarClass', 'bar bar' );
		var progressBarErrorClass = Joomla.getOptions( 'progressBarErrorClass', 'bar bar-danger' );
	}
	else {
		var progressBarContainerClass = 'progress progress-striped active';
		var progressBarClass = 'bar bar'
		var progressBarErrorClass = 'bar bar-warning';
	}

	jQuery( '#import-progress' ).remove();
	jQuery( '<div id="import-progress" class="import-progress">'
		+ '<div class="' + progressBarContainerClass + '" style="font-size:1rem;height:1.5rem">'
		+ '<div id="import-progress-bar-info" class="' + progressBarClass + '-info"></div>'
		+ '<div id="import-progress-bar-success" class="' + progressBarClass + '-success"></div>'
		+ '<div id="import-progress-bar-warning" class="' + progressBarClass + '-warning"></div>'
		+ '<div id="import-progress-bar-error" class="' + progressBarErrorClass + '"></div>'
		+ '</div>'
		+ '</div>').insertAfter( '#system-message-container' );

	eshiol.j2xml.importer( nodes, options );
}

/**
 * import items
 *
 * @param {array} nodes the array of item
 * @param {object} options
 *
 * @return  {boolean}  true on success.
 */
eshiol.j2xml.importer = function( nodes, options ){
	console.log( 'eshiol.j2xml.importer' );

	if ( nodes.length > 0 ){
		item = nodes.shift( );
		// Find the token so that it can be sent in the Ajax request as well
		//var token = Joomla.getOptions('csrf.token', '');
		var token = jQuery( '#installer-token' ).val();

		// Find the action url associated with the form - we need to add the token to this
		var url = 'index.php?option=com_j2xml&task=import.ajax_upload';

		var data = new FormData();
		var blob = new Blob( [new TextEncoder().encode(item)], { type: 'text/xml' } );
		data.append( 'install_package', blob, jQuery( '#j2xml_filename' ).val() );
		data.append( 'installtype', 'upload' );
		data.append( token, 1 );
		jQuery( '#j2xmlImportModal iframe' ).contents()
			.find( '#adminForm input:not(:radio)[name^=jform], #adminForm input:radio[name^=jform]:checked, #adminForm select[name^=jform]' ).each(
			function( index ){
				var input = jQuery( this );
				data.append( input.attr( 'name' ), input.val() );
			}
		);

		JoomlaInstaller.showLoading();

		jQuery.ajax( {
			url: url,
			data: data,
			type: 'post',
			processData: false,
			cache: false,
			contentType: false,
			error: function( error ){
				console.log( 'error' );
				console.log( error );
				JoomlaInstaller.hideLoading();
				eshiol.renderMessages( {'error': [error.responseText]} );
				//jQuery( '#import-progress' ).remove();
				jQuery( '#import-progress-bar-info' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
				jQuery( '#import-progress-bar-success' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
				jQuery( '#import-progress-bar-error' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
				jQuery( '#import-progress-bar-warning' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
				setTimeout( function( ) { jQuery( '#import-progress' ).remove(); }, 10000);
			}
		} ).done( function( res ){
			console.log( 'done' );
			if( res.messages ){
				jQuery.each( res.messages, function( type, message ){
					console.log(type + ' ' + message);
					if( type == 'notice' ){
						options.info++;
						jQuery( '#import-progress-bar-info' ).css( 'width', ( 100 * options.info / options.tot ) + '%' ).text( options.info );
					}
					else if( type == 'message' ){
						options.success++;
						jQuery( '#import-progress-bar-success' ).css( 'width', ( 100 * options.success / options.tot ) + '%' ).text( options.success );
					}
					else if( type == 'error' ){
						var j2xmlOptions  = Joomla.getOptions('J2XML');
						if (j2xmlOptions && j2xmlOptions.HaltOnError) {
							options.error = options.tot - options.info - options.success - options.warning;
							jQuery( '#import-progress-bar-error' ).css( 'width', ( 100 * options.error / options.tot ) + '%' ).html( message );
							nodes = [];
						}
						else {
							options.error++;
							jQuery( '#import-progress-bar-error' ).css( 'width', ( 100 * options.error / options.tot ) + '%' ).text( options.error );
						}
					}
					else{
						options.warning++;
						jQuery( '#import-progress-bar-warning' ).css( 'width', ( 100 * options.warning / options.tot ) + '%' ).text( options.warning );
					}
				} );

				eshiol.renderMessages( res.messages );
			}
			else if( res.message ){
				if( res.success ){
					options.success++;
					var progress = ( 100 * options.success / options.tot );
					jQuery( '#import-progress-bar-success' ).css( 'width', progress + '%' ).text( options.success );
					console.log( '#import-progress-bar-success.width: ' + progress );
					eshiol.renderMessages( {'message': [res.message]} );
				}
				else {
					options.error++;
					jQuery( '#import-progress-bar-error' ).css( 'width', ( 100 * options.error / options.tot ) + '%' ).text( options.error );
					eshiol.renderMessages( {'error': [res.message]} );
				}
			}
			else {
				options.tot--;
				jQuery( '#import-progress-bar-info' ).css( 'width', ( 100 * options.info / options.tot ) + '%' );
				jQuery( '#import-progress-bar-success' ).css( 'width', ( 100 * options.success / options.tot ) + '%' );
				jQuery( '#import-progress-bar-error' ).css( 'width', ( 100 * options.error / options.tot ) + '%' );
				jQuery( '#import-progress-bar-warning' ).css( 'width', ( 100 * options.warning / options.tot ) + '%' );
			}

			elements = document.getElementById( 'system-message-container' ).childNodes;
			if( typeof( elements ) != 'undefined' && elements != null ){
				element = elements[elements.length - 2];
				if( typeof( element ) != 'undefined' && element != null ){
					element.scrollIntoView();
				}
			}

			// Clean message queue and run import again
			jQuery.ajax( {
				url: 'index.php'
			} ).done( function( res ){
				if( nodes.length == 0 ){
					JoomlaInstaller.hideLoading();
				}

				eshiol.j2xml.importer( nodes, options );
			} );
		} );
	}
	else {
		jQuery( '#import-progress-bar-info' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
		jQuery( '#import-progress-bar-success' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
		jQuery( '#import-progress-bar-error' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
		jQuery( '#import-progress-bar-warning' ).removeClass( 'progress-bar-striped' ).removeClass( 'progress-bar-animated' );
		setTimeout( function( ) { jQuery( '#import-progress' ).remove(); }, 10000);
	}
}
