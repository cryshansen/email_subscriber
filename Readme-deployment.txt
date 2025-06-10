--ajax implementation
version 1.x
----src
	--Form 
		--TestForm.php -- builds form sends back ajax response with ajax tied to the button
	--Plugin
		--Block
			--TestEmailSubscribeBlock.php  -- renders the form in a block
--libraries.yml -- assign the library files -- library attached in form build 
	--test_email_styles
		--js
			--test-email.js
		--css
			--test_email_styles.css			
--.module  -- assigns the twig template to use attach form array to
--.routing.yml
	--form-pointer test-form route to test form as is  against 

Integrate these changes into the empt_email_subscribe form for new testing. disabled calls to third parties.


//Modal Changes	
	version 1.x.1
-- test-form.html.twig --- add modal open button.
-- routing file for /modal_form  
-- modal
	--js/test-email-modal.js -- opens modal
----src
	-- controller
		--modalcontroller.php

--themes/emtp
	--templates
		--page.html.twig -- place modal code here. TODO: adjust ids so that block and modal can be on same page. place button here block placement insert the library in the page.html.twig so that the modal will have the javascript to respond.TODO: finalize




For Production
to upgrade to these two for the block placement on the front page.
EEmailSubscribeBlock.php
EmailSubscribeForm.php
-- one js file to clear the form after success


Modal 
--EmailFormModal

page.html.twig to replace the modals to one only being the  email-form-modal 'emailmodalform' -- EmtpEmailModalForm.php


Page Email Subscribe Block -- the block to replace in production on front page. 

--page.html.twig  -- contains new modal and button links to modal -- issue is error on id of button. 





Sept 12 2024 ----  DONOT PROMOTE TO PRODUCTION!!!!!!!

UnsubscribeForm.php
EMTPEmailSubscribeConfigForm.php

emtp_email_subscribe.routing yml

emtp_email_subscribe install file


addition of unsubscriber and push to brevo and Hubspot.
add a new database table for tracking the actions of users during the unsubscriber button
use javascript to hit a table of actions on the page. reading any button?
on click events


Removed : in routing.yml
emtp_email_subscribe.unsubscribe:
  path: '/unsubscribe/{email}'
  defaults:
    _controller: '\Drupal\emtp_email_subscribe\Controller\UnsubscribeController::unsubscribe'
    _title: 'Unsubscribe'
  requirements:
    _permission: 'access content'
  options:
    no_cache: 'TRUE'
	




	
	
Sept 23 2024	 ----  DONOT PROMOTE TO PRODUCTION!!!!!!!

List all test of do for automated user activities. 
selenium can run tests automation tests

All Forms
	Forms list urls
All pages that use download features
	download-emtp
	
all pages that require logged in featured requirement
	user references
	download page
	exchange-platform
	technical presentations
	
Pages of user display / modals 
	
Pages that use carousel
pages that use accordion