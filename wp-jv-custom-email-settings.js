/*
// Scripts for WP JV Custom Email Settings
// @version: 2.0
*/
jQuery(document).ready(function($){
	/************************************************************************************************************/
	/* Invoke notification sending */
	/************************************************************************************************************/	    
	$('#btnSendNotification').click(function(){	
		//Disable button	
		$('#btnSendNotification').attr('disabled', true);				
	
		//Display loading icon while it is sending out emails
		document.getElementById('dProgress').style.display = 'inline-block';		
		document.getElementById('jv-ces-message').style.display = 'inline-block';	
		document.getElementById('jv-ces-message').innerHTML='Sending email notifications...';		
				
		$('#dProgress')
			.ajaxStart(function() {								
				$(this).show();
			})
			.ajaxStop(function() {
				$(this).hide();				
				$(this).unbind("ajaxStart"); //added to fix unnecessary turning on the loading icon by other scripts running on the page
			})
		;
	
		//Get the post id from hidden input
		var postid = document.getElementById('jv-notification-postid').value;			
		
		//Send e-mails
		data = {
                action			: 'wp_jv_ces_send_notification_manual',
                url				: ajaxurl,
                type			: 'POST',
				dataType		: 'text',
				'post_id'		: postid,
				wp_jv_ces_nonce	: wp_jv_ces_obj.wp_jv_ces_nonce
            };				
		$.post(ajaxurl, data, function(response){		
			//to debug uncomment the following line
			/*
			alert (	'error:'+response.error+'\n'+
					'error_msg:'+response.error_msg+'\n'+					
					'logged_count: '+response.logged_count+'\n'+
					'sent_count:'+response.sent_count+'\n'+
					'sending_error_count:'+response.sending_error_count+'\n'+
					'error_code:'+response.error_code+'\n'+
					'log_page_url:'+response.log_page_url);
			*/
			if (response.error) {
				if (response.logged_count==0 || response.sent_count==0) {
					document.getElementById('jv-ces-message').innerHTML='Error sending emails.';
					alert (response.error_msg + '\n\n[Error: '+ response.error_code + ']');
				}
				else {
					document.getElementById('jv-ces-message').innerHTML=response.sent_count +' email(s) out of ' + (response.sent_count+response.sending_error_count) + ' sent with ' + (response.sent_count+response.sending_error_count-response.logged_count) + ' log issues. Check <a href="' + response.log_page_url + '">log</a> for details.';					
					$('#btnSendNotification').attr('value', 'Re-send notification email(s)');
				}
			} else {					
					//Emails sent successfully					
					document.getElementById('jv-ces-message').innerHTML='<br>' + response.sent_count + ' notification(s) sent. Check <a href="' + response.log_page_url + '">log</a> for details.';
					$('#btnSendNotification').attr('value', 'Re-send notification email(s)');
					}
			//Re-enable button
			$('#btnSendNotification').attr('disabled', false);		
		});				
	});			
});