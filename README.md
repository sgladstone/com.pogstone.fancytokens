Install this extension as you would any other CiviCRM extension. 

Additional mail merge tokens for CiviCRM for use in emails, mass emails, or PDF letters. All the token descriptions start with "Community News & Enagagement". 

Main features:

   A) There is one token for each active contribution page. This token will produce a hyperlink that includes the checksum for the recipient. The clickable text will be the title of the contribution page
   
   B) There are several tokens for different time periods of upcoming events, such as 7 days, 3 weeks, and 3 months. After inserting the token into the body of the message, you can change the numeric portion of the token to whatever you prefer. This token will produce a nicely formatted HTML list of upcoming events, ie events starting after now() but before the end of the time period chosen. All event titles will be hyperlinked to the event information page. If the event allows for online registration, then there will also be a second hyperlink (including the checksum) pointing to the registration page. The text for this registration link will be the value in 'Registration Link Text' in the event configuration. The event listing will also include the event summary. 
   
   C) There is a token for each of the next X event registration pages ( i.e. future events that also have online registration enabled).  The token includes the event ID which can be manually changed to any valid event ID. The hyperlink will include the checksum for the recipient.
   
   D) There is one token for each active profile that does not use any fields that would prevent it from being used as a stand-alone profile form. For example, it does not create tokens for profiles that use participant or contribution fields. This token will produce a hyperlink that includes the checksum for the recipient. The clickable text will be the title of the profile.  

  E) If this extension is running under Drupal and the CiviCRM-WebForm integration module is installed, then one token will be created for each published WebForm. 
