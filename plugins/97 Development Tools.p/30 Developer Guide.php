<?php

$this->pageArea.='
<h3>The API</h3>
<p>
The API is a JSON-based API that has a highly consistent structure.
It is designed to be wrapped in a communication function, and developers are encouraged to create
a wrapper class so that they do not work with the JSON directly. For example, the website
has a function <span style="font-family: monospace;">getResponse()</span> which takes four arguments, the first three are strings,
and the fourth is a PHP array. It returns a PHP standard class object, making it easy to manipulate
the result. The API will consistently return boolean values as "true" or "false", numbers as unquoted
numerals, a message as a string that can be used to determine the reason for the response, and results
as an object or an array depending on how your implementation of JSON interprets them. You can rely on these
entries always being returned.
</p>

<h3>Using the API Test Page</h3>
<p>
Inside Phylobyte CMS, under the "Development Tools" menu is an API test page. The API test page is designed
to make the API easy to explore and work with. The test page is divided into three main areas. The left column presents the API functions
and API test page settings. The right side is the API result preview. When an API call is passed, the exact parameters will be displayed in
Phylobyte\'s "Pile" as an alert. The top box on the left column lets you browse the functions available in the API. They are organized in groups.
Select a function group from the drop down, and click "Select". Many of the API functions require a user ID and token in order to function.
To make it easier, the next box allows you to set a default user ID and token that will be automatically filled in in the API function test
forms. The API is designed to be easily readable, and most responses that are displayed in the API response preview should be reasonably formatted. 
</p>

<h3>Logging In and Registering Accounts</h3>
<p>
For communication, the API uses a token-based authentication scheme. Passwords do not need to be sent over the API, rather,
send the password hashed as SHA-1. For this reason, if a user is registering, you need to check that the password and repeated
password are the same and over four letters on the <em>client</em> side. Once the login is successful, a numeric token is returned
along with the user ID. You need to save these values, they are what let you access the user\'s account functions. The token
will change whenever a user logs in on any device, so you may also want to save the user name so that you can perform the login again
without prompting the user. This is especially important on a mobile device, where you do not want the user logged out unless they
choose to log out.
</p>
';

?>

