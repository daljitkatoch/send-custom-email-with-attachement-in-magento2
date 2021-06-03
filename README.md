# Magento 2 Custom Contact Form With Attachment 

## Field to attach files to the contact form

Installed and tested on magento version 2.3.4

# Usage instructions

Customer uses contact form to send file along with message!

Url of from after install : http://www.example.com/request

<img src="https://user-images.githubusercontent.com/16095028/120619750-eb23a200-c479-11eb-96c3-abf0680a9acf.png" style="border:1px solid #eee; max-width:600px"/>

## Run These commands in ssh after uploading file to app/code folder

php bin/magento setup:upgrade

php -d memory_limit=-1 bin/magento setup:static-content:deploy -f

php -d memory_limit=-1 bin/magento cache:flush



