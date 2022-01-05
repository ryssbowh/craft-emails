# Emails for Craft CMS ^3.5

Replace Craft System messages by database driven email templates editable in the backend.

![List](/images/list.png)

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

![Content](/images/content.png)

## Logging

You can choose to save a log of each email sent in database for future reference. The logs are compressed in database so not to take too much space.

![Logs](/images/logs.png)

## Project Config

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

![Config](/images/config.png)

## Sending emails

This plugin doesn't send email, this is for you to do whenever you want to :

```
$variables = [
    'user' => 'John'
];

Craft::$app->getMailer()
    ->composeFromKey('email_key', $variables)
    ->setTo('recipient@test.com')
    ->send();
```

That's it, the email will automatically be modified according to its config before it's sent.

## Roadmap

- Add a trigger system to send emails automatically when something happens on the system