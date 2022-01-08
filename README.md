# Emails for Craft CMS ^3.5

Replace Craft System messages by database driven email templates editable in the backend.  
Define email shots to send to various users.

## Emails

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

### Logging

You can choose to save a log of each email sent in database for future reference. The logs are compressed in database so not to take too much space.

![Logs](/images/logs.png)

### Project Config

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

## Email shots

Define a new email shots using the dashboard :

![Shots dashboard](/images/shots.png)

You can either save an email shot for future use, or create a quick shot that will be sent instantly.

Each of them will require an email, and some emails to send to, which can come from 3 places :
- A source : A source of emails, the default comes with a "All users" and a source for each user group. More source can be defined
- Users : Choose users
- Email : Enter emails

Email shots can use the queue to send emails, using the queue present advantages as emails will be sent in the background, but you do need to run the queue manually if you're scheduling email shots.

![Edit Shot](/images/shot.png)

### Logs

See what email shot has been sent to which email with the logs :

![Config](/images/shots-logs.png)

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

If the email you send with a shot expects bespoke variables, you will need to define them manually before the shot is sent :

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

`./craft emails/shot/send shot-handle`

## Permissions

6 new permissions :

- Access Emails (under General)
- Add and delete email templates
- Modify emails content
- Modify emails config 
- See emails logs (applies to shot logs as well)
- Delete emails logs (applies to shot logs as well)
- Manage email shots

To add attachements users will need "View volume" and potentially "View files uploaded by other users" for one or several volumes.

## Roadmap

- Add a trigger system to send emails automatically when something happens on the system