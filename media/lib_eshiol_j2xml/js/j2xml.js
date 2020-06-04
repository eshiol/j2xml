/**
 * @package		Joomla.Libraries
 * @subpackage	eshiol.J2XML
 *
 * @since		16.11.288
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// Avoid `console` errors in browsers that lack a console.
( function(){
	var methods = [
		'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
		'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
		'profile', 'profileEnd', 'table', 'time', 'timeEnd', 'timeStamp',
		'trace', 'warn'
	];
	console = window.console = window.console || {};
	methods.forEach( function( method ){
		if( !console[method] ){
			console[method] = function (){};
		}
	});

	console.dump = function( object ){
		if( window.JSON && window.JSON.stringify ){
			console.log( JSON.stringify( object ) );
		}
		else{
			console.log( object );
		}
	};
}() );

Joomla.JText.strings['SUCCESS'] = 'Message';

if( typeof( eshiol ) === 'undefined' ){
	var eshiol = {};
}

if( typeof( eshiol.j2xml ) === 'undefined' ){
	eshiol.j2xml = {};
}

if( typeof( eshiol.j2xml.convert ) === 'undefined' ){
	eshiol.j2xml.convert = [];
}

eshiol.j2xml.version = '__DEPLOY_VERSION__';

console.log( 'J2XML Library v' + eshiol.j2xml.version );

/**
 * Remove messages
 *
 * @param	object	messageContainer	the messages container
 *
 * @return  void
 */
if( typeof( eshiol.removeMessages ) === 'undefined' ){
	eshiol.removeMessages = function( messageContainer ){
		if( typeof( messageContainer ) === 'undefined' ){
			messageContainer = jQuery('#system-message-container');
		}

		// Empty container
		messageContainer.empty();

		// Fix Chrome bug not updating element height
		messageContainer.css( 'display', 'none' );
		messageContainer.outerHeight( true );
		messageContainer.css( 'display', '' );
	};
}

/**
 * Render messages sent via JSON
 *
 * @param	object	messages			JavaScript object containing the messages to render
 * @param	object	messageContainer	the container where render the messages
 * @return	void
 */
if( typeof( eshiol.renderMessages ) === 'undefined' ){
	eshiol.renderMessages = function( messages, messageContainer ){
		if( typeof( messageContainer ) === 'undefined' ){
			messageContainer = jQuery( '#system-message-container' );
		}

		jQuery.each( messages, function( type, message ){
			if( type == 'message' ){
				type = 'success';
			}

			var div = messageContainer.find( 'div.alert.alert-' + type );
			if( !div[0] ){
				jQuery( '<button/>', {
					'class': 'close',
					'data-dismiss': 'alert',
					'type': 'button',
					'html': '&times;'
				} ).appendTo( messageContainer );

				div = jQuery( '<div/>', {
					'class': 'alert alert-' + type
				} ).appendTo( messageContainer );

				jQuery( '<h4/>', {
					'class' : 'alert-heading',
					'html': Joomla.JText._( type, type.charAt( 0 ).toUpperCase() + type.slice( 1 ) )
				} ).appendTo( div );
			}
			else{
				div = div[0];
			}

			jQuery.each( message, function( index, item ){
				jQuery( '<p/>', {
					'html': item
				} ).appendTo( div );
			} );
		} );
	};
}

eshiol.j2xml.codes = [
	'message', // LIB_J2XML_MSG_ARTICLE_IMPORTED 0
	'notice', // LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED 1
	'message', // LIB_J2XML_MSG_USER_IMPORTED 2
	'notice', // LIB_J2XML_MSG_USER_NOT_IMPORTED 3
	'notice', // 'message', // not used: LIB_J2XML_MSG_SECTION_IMPORTED 4
	'notice', // not used: LIB_J2XML_MSG_SECTION_NOT_IMPORTED 5
	'message', // LIB_J2XML_MSG_CATEGORY_IMPORTED 6
	'notice', // LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED 7
	'message', // LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED 8
	'notice', // LIB_J2XML_MSG_ERROR_CREATING_FOLDER 9
	'message', // LIB_J2XML_MSG_IMAGE_IMPORTED 10
	'notice', // LIB_J2XML_MSG_IMAGE_NOT_IMPORTED 11
	'message', // LIB_J2XML_MSG_WEBLINK_IMPORTED 12
	'notice', // LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED 13
	'notice', // not used: LIB_J2XML_MSG_WEBLINKCAT_NOT_PRESENT 14
	'error', // LIB_J2XML_MSG_XMLRPC_NOT_SUPPORTED 15
	'notice', // LIB_J2XML_MSG_CATEGORY_ID_PRESENT 16
	'error', // LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED 17
	'error', // LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN 18
	'error', // JERROR_ALERTNOTAUTH 19
	'message', // LIB_J2XML_MSG_TAG_IMPORTED 20
	'notice', // LIB_J2XML_MSG_TAG_NOT_IMPORTED 21
	'message', // LIB_J2XML_MSG_CONTACT_IMPORTED 22
	'notice', // LIB_J2XML_MSG_CONTACT_NOT_IMPORTED 23
	'message', // LIB_J2XML_MSG_VIEWLEVEL_IMPORTED 24
	'notice', // LIB_J2XML_MSG_VIEWLEVEL_NOT_IMPORTED 25
	'message', // LIB_J2XML_MSG_BUTTON_IMPORTED 26
	'notice', // LIB_J2XML_MSG_BUTTON_NOT_IMPORTED 27
	'error', // LIB_J2XML_MSG_UNKNOWN_ERROR 28
	'warning', // LIB_J2XML_MSG_UNKNOWN_WARNING 29
	'notice', // LIB_J2XML_MSG_UNKNOWN_NOTICE 30
	'message', // LIB_J2XML_MSG_UNKNOWN_MESSAGE 31
	'notice', // LIB_J2XML_MSG_XMLRPC_DISABLED 32
	'message', // LIB_J2XML_MSG_MENUTYPE_IMPORTED 33
	'notice', // LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED 34
	'message', // LIB_J2XML_MSG_MENU_IMPORTED 35
	'notice', // LIB_J2XML_MSG_MENU_NOT_IMPORTED 36
	'notice', // LIB_J2XML_ERROR_COMPONENT_NOT_FOUND 37
	'message', // LIB_J2XML_MSG_MODULE_IMPORTED 38
	'notice', // LIB_J2XML_MSG_MODULE_NOT_IMPORTED 39
	'message', // LIB_J2XML_MSG_FIELD_IMPORTED 40
	'notice', // LIB_J2XML_MSG_FIELD_NOT_IMPORTED 41
	'message', // LIB_J2XML_MSG_USERNOTE_IMPORTED 42
	'notice', // LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED 43
	'message', // LIB_J2XML_MSG_FIELDGROUP_IMPORTED 44
	'notice', // LIB_J2XML_MSG_FIELDGROUP_NOT_IMPORTED 45
	'notice' // LIB_J2XML_MSG_USER_SKIPPED 46
];

eshiol.j2xml.sendItem = function( options, params ){
	if( options.cids.length ){
		var cid = options.cids.shift();
		var progress = Math.floor( 100 * options.n / options.tot );
		window.parent.jQuery( '#send-progress-bar' ).css( 'width', progress + '%' ).attr( 'aria-valuenow', options.n );
		window.parent.jQuery( '#send-progress-text' ).html( Joomla.JText._( 'LIB_J2XML_SENDING' ).replace( '%s', progress + '%' ) );
		options.n++;

		console.log( 'exporting data from ' + options.export_url);
		console.log( params );

		Joomla.request( {
			url: options.export_url + '&cid[]=' + cid,
			data: Object.keys( params ).map( function( key ){
				return encodeURIComponent( key ) + '=' + encodeURIComponent( params[key] );
			} ).join( '&' ),
			onSuccess: function onSuccess( resp ){
				console.log( 'export.onSuccess' );
				console.log( 'sending data via xmlrpc to ' + options.remote_url );

				var r = JSON.parse( resp );
				p = params;
				delete p['compression'];
				console.log( p );
				jQuery.xmlrpc( {
					url: options.remote_url,
					methodName: 'j2xml.importAjax',
					params: [r.data, JSON.stringify( p )],
					xhrFields: {
						withCredentials: true
					},
					// beforeSend: function ( xhr ){
						// Set the headers
						// xhr.setRequestHeader( 'Authorization', 'Basic ' + btoa( username + ':' + password ) );
						// xhr.setRequestHeader( 'Authorization', 'Barer ' + token );
					// },
					success: function( response, status, jqXHR ){
						console.log( 'send.onSuccess' );
						window.parent.jQuery( 'input:checkbox[name=\'checkall-toggle\']' ).prop( 'checked', false );
						window.parent.jQuery( 'input:checkbox[name=\'cid\[\]\'][value=\'' + cid + '\']' ).prop( 'checked', false );
						jQuery.each( response, function( index, messages ){
							messages.forEach( function( item ){
								console.log( item );
								msg = new Object();
								if( item.code in eshiol.j2xml.codes ){
									t = eshiol.j2xml.codes[item.code];
								}
								else{
									t = 'notice';
								}
								msg[t] = [item.message];
								eshiol.renderMessages( msg, options.message_container );
							} );
						} );

						eshiol.j2xml.sendItem( options, params );
					},
					error: function( jqXHR, status, error ){
						console.log( 'send.onError' );

						msg = new Object();
						if ( typeof error === 'object' ){
							if( error.code in eshiol.j2xml.codes ){
								t = eshiol.j2xml.codes[error.code];
							}
							else{
								t = 'error';
							}
							msg[t] = [error.message];
						}
						else{
							msg['error'] = [error];
						}
						eshiol.renderMessages( msg, options.message_container );

						eshiol.j2xml.sendItem( options, params );
					}
				});
			},
			onError: function onError( xhr ){
				if( xhr.status > 0 ){
					Joomla.renderMessages( Joomla.ajaxErrorsMessages( xhr ) );
				}

				eshiol.j2xml.sendItem( options, params );
			}
		} );
	}
	else{
		window.parent.jQuery('#send-progress').remove();
	}
}

eshiol.j2xml.send = function ( options ){
	console.log( 'eshiol.j2xml.send' );
	options.cids = [];
	options.tot = 0;
	options.n = 0;

	if (typeof Joomla.getOptions !== "undefined") {
		var progressBarContainerClass = Joomla.getOptions( "progressBarContainerClass", "progress progress-striped active" );
		var progressBarClass = Joomla.getOptions( "progressBarClass", "bar bar-success" );
	}
	else {
		var progressBarContainerClass = "progress progress-striped active";
		var progressBarClass = "bar bar-success"
	}

	window.parent.jQuery( '#system-message-container' ).prepend(
			'<div id="send-progress" class="send-progress">'
			+ '<div class="' + progressBarContainerClass + '">'
			+ '<div id="send-progress-bar" class="' + progressBarClass + '" aria-valuenow="0" aria-valuemin="0" aria-valuemax="' + options.tot + '"></div>'
			+ '</div>'
			+ '<p class="lead">'
			+ '<span id="send-progress-text" class="sending-text">'
			+ Joomla.JText._( 'LIB_J2XML_SENDING' ).replace( '%s', '0%' )
			+ '</span>'
			+ '</p>'
			+ '</div>'
		);

	var params = {};
	jQuery( '#adminForm input:not(:radio)[name^=jform], #adminForm input:radio[name^=jform]:checked, #adminForm select[name^=jform]' ).each( function( index ){
		var input = jQuery( this );
		var name = input.attr( 'name' ).match(/jform\[(.*)\]/)[1];

		if( name.substr( 0, 5 ) == 'send_' ){
			name = name.substr( 5 );
		}
		if( ['cid', 'remote_url'].indexOf( name ) == -1 ){
			params[name] = input.val();
		}
	});
//	['cid', 'remote_url'].forEach(function(element){
//		delete params[element];
//	});

	navigator.__defineGetter__( 'userAgent', function(){ return eshiol.j2xml.version } );

	window.parent.jQuery( 'input:checkbox[name=\'cid\[\]\']:checked' ).each( function(){
		options.cids.push( jQuery( this ).val() );
		options.tot++;
	});

	eshiol.j2xml.sendItem( options, params );
}

eshiol.XMLToString = function( xmlDom )
{
	// console.log( "eshiol.XMLToString" );
	// console.log( xmlDom );
	x = ( typeof XMLSerializer !== "undefined" )
		? ( new window.XMLSerializer() ).serializeToString( xmlDom )
		: xmlDom.xml;
	// console.log( x );
	return x;
}

if( typeof strstr === "undefined" ){
	function strstr( haystack, needle, bool ){
		var pos = 0;

		haystack += "";
		pos = haystack.indexOf( needle );
		if( pos == -1 ){
			return false;
		}
		else{
			if( bool ){
				return haystack.substr( 0, pos );
			}
			else{
				return haystack.slice( pos );
			}
		}
	}
}
