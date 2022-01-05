# Emails for Craft CMS ^3.5

Replace Craft System messages by database driven email templates editable in the backend.

Once this plugin is installed, you'll be able to change, for each email :
- It's subject
- It's body
- The email it's coming from, will default to system email config
- The name it's coming from, will default to system email config
- The reply to address, will default to system email config
- List of email addresses to CC
- List of email addresses to BCC
- Add assets as attachements
- Have a different redactor config

The system will count how many emails are sent and can also log each email sent in database for future reference. The logs are compressed in database so not to take too much space.

You can choose which email parameter(s) are considered as config. The chosen ones will be included in the project config and will be modified from an environment to another, possible parameters are:
- Heading
- From
- Name from
- Reply to email
- Cc
- Bcc
- Subject
- Body
- Attachements

You can define as many emails as you need. This plugin doesn't send email though, this is for you to do whenever you see want to :

```
Craft::$app->getMailer()
    ->composeFromKey('email_key', $variables)
    ->setTo($user)
    ->send();
```

That's it, the email will automatically be modified according to its config before it's sent.