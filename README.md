Yii-Sentry
==========

Sentry log route for Yii framework

The extension allows to setup multiple sentry clients. 
An example use case could be using Sentry's [projects](https://www.getsentry.com/docs/teams-and-projects/) to log to your web, worker and/or back-end project.

### Requirements

* Yii Framework > 1.1.x (Have not test any other frameworks)
* [Sentry Account](https://www.getsentry.com/) - OR - Your own [Sentry server](http://sentry.readthedocs.org/en/latest/quickstart/)

## Usage

### Download

Unzip the extension under ***protected/extensions/yii-sentry***

### Configure

You will need to configure 2 components, namely ***RSentryClient*** & ***RSentryLogRoute***

***RSentryClient*** is the client component and encapsulates the [raven-php](https://github.com/getsentry/raven-php) client.

***RSentryLogRoute*** is the LogRouter that uses a client to sends the logs

```php
return array(
    .....
    'components'=>array(
        'sentry'=>array(
            'class'=>'ext.yii-sentry.components.RSentryClient',
            'dsn'=>'<YOUR_DSN>',
        ),
        'log'=>array(
            'class'=>'CLogRouter',
            'routes'=>array(
                array(
                    'class'=>'ext.yii-sentry.components.RSentryLogRoute',
                    'levels'=>'error, warning',
                ),
                .....
            ),
        ),
    ),
);
```

#### Optional client configuration

```php
'sentry'=>array(
    'enabled'=>true // Optional (Defaults to true) - Whether to enable sending logs to Sentry, i.e. turn ON/OFF
    'options'=>array( // Optional (Defaults to empty array) - The Raven_Client configuration options, see: https://github.com/getsentry/raven-php#configuration
        'name'=>'my-server-hostname',
        'tags'=>array(
            'php_version'=>phpversion(),
        ),
    ),
),
```

#### Optional LogRoute configuration

```php
array(
    'sentryComponent'=>'sentry', // Optional (Defaults to 'sentry') - The component ID of the RSentryClient to send the logs to
    'ravenLogCategory'=>'raven' // Optional (Defaults to 'raven') - Any errors encountered within the extension will be logged with this category
),
```

### Acknowledgements

* Thanks to the Sentry Team for [raven-php](https://github.com/getsentry/raven-php) and of course, [Sentry](https://www.getsentry.com/)
* Thanks to [@rolies106](https://github.com/rolies106) for [yii-sentry-log](https://github.com/rolies106/yii-sentry-log) which served as inspiration for this extension