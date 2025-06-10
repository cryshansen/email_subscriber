# email_subscriber
A Drupal email newsletter signup with both a block placement and a modal view. This requires placement with theme files to place the modal in page.html.twig at the moment. Future is to inject the form rather than html coded.
Dependency:
 `twig_tweak`
composer require 'drupal/twig_tweak:^3.4'


Installation Guide:

Navigate to the configuration page under web services for adding api keys for both brevo and hubspot. 
url route : admin/config/services/email-subscribe




Modal Installation v1: 
This requires the bootstrap modal component to exist in the html.html.twig of the current theme. 
Then the button to launch is easily placed for initiating the modal into view. It must reside at this level as bootstrap appends the hide/show against the body tag and the modal must be at that hierarchy level.
use a link or button to trigger the modal:
<a id="email-modal-button" class="secondary-nobutton" data-bs-toggle="modal" data-bs-target="#email-form-modal" alt="Subscribe to our newsletter" title="Subscribe to our newsletter"> <i class="fa-solid fa-newspaper fa-lg"></i></a>

Add the below code to the theme html.html.twig file: 

This below code must reside in the html.html.twig just before the `</body>`

```html
<!-- Email Modal -->
    <div class="modal fade" id="email-form-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-body">
              <button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="Close"></button>
              {{ drupal_block('email_subscribe_block') }}
              <div id="emailmodalform-messages"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

<!-- email Modal end -->
```

NOTE: There is a dependency for `twig_tweak` for the modal to work using `drupal_block`  composer require 'drupal/twig_tweak:^3.4'


Block  Installation:
This section refers to the EmailSubscribeForm.php file that is placed as a block on any page. 
Navigate to the `Block structure` and choose `place block` button in the `region`  of your theme placeholders where you would like the form to show.  The label will be `Email Subscriber Page Block` select it and configure the page access or other items. Save and Test.


Routes:
Below itemized routes are used to access different pages in a newsletter workflow between the web and third party emailing systems
email_subscriber.emailsubscribe
This route is a page level of the newsletter subscriber form. It uses ajax for submission response. 
`/newsletter-signup` should show the form and testing can be done using this form.


email_subscriber.signup:
This route manages the Brevo double opt-in sign-up. It correlates to the subscribecontroller.php file. The pattern to align  /signup/{contact.EMAIL} link in brevo becomes  site.com/signup/emailaddress on the website.
This requires a list in Brevo,  tags listen for the url and automatically reflect the link was clicked and recorded the email Please see Brevo Setup.

The back-end php records the confirmed signup via the `updated` timestamp, confirm_status flag when landing on this route details are found in the email_subscriber.routing.yml file 

email_subscriber.unsubscribe_form:
This route manages an unsubscribe function on the website when a user unsubscribes from newsletters. The route 
In the backend the form records an `unsubcribed` boolean flag and `unsubscribe_requested` time stamp to handle the third party configurations


Brevo Setup:
Follow Brevo API key can be found under user menu > SMTP & API you will need to configure the email access to your server account.
Create a list like `newsletter-signup`
Edit the newsletter form default add firstname , lastname attributes to the form save and progress to Double confirmation email. You must have a transactional account.

Under the transactional section workflows can be created to handle these components. Regarding this module you need to uncomment the calls to brevo that uses this features. for quick install it is not needed.
https://developers.brevo.com/reference/createdoicontact


Please NOTE: There are additional readme from originating site of inclusion as cleanup was not a priority. 
To manage the changes cleanup is required there are notes that pertain to certain changes made in emtp.com from a development version and a deployed version. Readme-deployment.txt

TODO: Clean up any residual information, code deprecation or correct errors as they arise.