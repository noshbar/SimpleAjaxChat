#Simple HTTP Ajax Chat with Encryption

###Dear god why?
Really paranoid network administrators sometimes block just about everything, local chat programs, web-based HTTPS chat programs, IRC, SSH... everything except HTTP.<br>
But no one wants that paranoid administrator to sniff the network and see clear-text HTTP messages about how insane he is, so we encrypt the messages using JavaScript.

###How
* jQuery
* CryptoJS
* This simply polls a server for the contents of a log-file.
* The server returns only new lines since the last poll
* Sending a message to the server adds it to the log
* Messages are encrypted using AES and a user-known key before being sent to the server, and decrypted before being displayed
* Chrome desktop alerts are supported

###Improvements to be made
* Polling time decay when there is focus (not only when in idle state)
* Testing in browsers other than Chrome and FireFox
* Some sort of long-polling that doesn't kill Apache?