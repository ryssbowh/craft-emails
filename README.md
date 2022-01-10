# Emails for Craft CMS ^3.5

Replace Craft System messages by database driven email templates editable in the backend.  
Define email shots to send to various users.

## Emails

![List](/images/list.png)

Change the subject, body and attachements of Craft system emails and define new ones.  
Emails config are saved in project config and will populate from an environment to another, the config includes :
- The email identifier (key)
- The email heading
- The template used for rendering the email, defaults to 'emails/template'
- Whether the email is plain text or html
- Redactor config for the body
- Some instructions for the body
- The email it's coming from, will default to system email config
- The name it's coming from, will default to system email config
- The reply to address, will default to system email config
- List of email addresses to CC
- List of email addresses to BCC
- Wether the logs of sent emails shoud be saved

![Config](/images/config.png)

Emails content (subject, body and attachements) is translatable, and will not be saved in project config.

There will be one translation available per site language, define new sites with different languages to access translations. Your emails translation will be used automatically when sending emails from different websites.  
:warning: Changing the language of the primary site will change the language of the emails associated.

Emails can be previewed.

![Content](/images/content.png)

### Logs

Enabling logs will allow resending the emails

![Logs](/images/logs.png)

### Sending emails

Sending an email manually :

```
$variables = [
    'user' => 'John'
];

Craft::$app->getMailer()
    ->composeFromKey('email_key', $variables)
    ->setTo('recipient@test.com')
    ->send();
```

The email will automatically be modified according to its config before it's sent.

You may modify variables passed to any email using this event :

```
Event::on(
    Mailer::class, 
    Mailer::EVENT_BEFORE_PREP, 
    function (MailEvent $e) {
        $e->message->variables['name'] = 'value';
    }
);
```

## Email shots

Define a new email shots using the dashboard :

![Shots dashboard](/images/shots.png)

You can either save an email shot for future use, or create a quick shot that will be sent instantly.

Each of them will require an email, and some emails to send to, which can come from 4 places :
- A source : A source of emails, the default comes with a "All users" and a source for each user group. More source can be defined
- Users : Choose users
- Email : Enter emails
- Mailchimp lists : Enter your api key in the settings to enable your lists. Lists will be cached for 30min by default

Email shots can use the queue to send emails, using the queue present advantages as emails will be sent in the background, but you do need to run the queue manually if you're scheduling email shots.

![Edit Shot](/images/shot.png)

### Logs

See what email shot has been sent to which emails and by whom with the logs :

![Shot logs](/images/shot-logs.png)

### Define more sources

Respond to the event :

```
Event::on(
    EmailSourceService::class,
    EmailSourceService::EVENT_REGISTER,
    function (RegisterEmailSourcesEvent $e) {
        $e->add(new MyEmailSource);
    }
);
```

Email sources must implement `EmailSourceInterface`. Exceptions will be thrown when registering sources which handles are already defined.

### Variables

You can define variables manually before the shot is sent, they will be passed to the email :

```
Event::on(
    EmailShotsService::class,
    EmailShotsService::EVENT_BEFORE_SEND,
    function (SendEmailShotEvent $e) {
        $e->shot->variables = [
            'var' => 'value'
        ];
    }
);
```

For global variables, refer to the [documentation](https://craftcms.com/docs/3.x/dev/global-variables.html#craft)

## Commands

Send an email shot :

`./craft emails/shot/send shot-handle`

## Permissions

8 new permissions :

- Access Emails (under General)
- Add and delete email templates
- Modify emails content
- Modify emails config 
- See logs (emails & shots)
- Delete logs (emails & shots)
- Manage email shots
- Send emails

To add attachements users will need "View volume" and potentially "View files uploaded by other users" for one or several volumes.

## Requirements

php >= 7.4  
Craft >= 3.5  
[Redactor plugin](https://plugins.craftcms.com/redactor), this is automatically installed by composer but doesn't need to be installed/enabled in the cms

## Installation

`composer require ryssbowh/craft-emails`

## Roadmap

- Add a trigger system to send emails automatically when something happens on the system