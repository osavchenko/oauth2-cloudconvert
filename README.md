# CloudConvert Provider for OAuth 2.0 Client

[![Source Code](https://img.shields.io/badge/source-osavchenko/oauth2--cloudconvert-blue.svg?style=flat-square)](https://github.com/osavchenko/oauth2-cloudconvert)
[![Latest Version](https://img.shields.io/github/release/osavchenko/oauth2-cloudconvert.svg?style=flat-square)](https://github.com/osavchenko/oauth2-cloudconvert/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/osavchenko/oauth2-cloudconvert/blob/master/LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/osavchenko/oauth2-cloudconvert/CI?label=CI&logo=github&style=flat-square)](https://github.com/osavchenko/oauth2-cloudconvert/actions?query=workflow%3ACI)
[![Quality Score](https://img.shields.io/scrutinizer/g/osavchenko/oauth2-cloudconvert.svg?style=flat-square)](https://scrutinizer-ci.com/g/osavchenko/oauth2-cloudconvert)
[![Codecov Code Coverage](https://img.shields.io/codecov/c/gh/osavchenko/oauth2-cloudconvert?label=codecov&logo=codecov&style=flat-square)](https://codecov.io/gh/osavchenko/oauth2-cloudconvert)

This package provides [CloudConvert](https://cloudconvert.com/) OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/osavchenko/oauth2-cloudconvert).

## Installation

To install, use composer:

```
composer require osavchenko/oauth2-cloudconvert
```

## Usage

Usage is the same as The League's OAuth client, using `\Osavchenko\OAuth2\Client\Provider\CloudConvert` as the provider.

### Authorization Code Flow

```php
$provider = new Osavchenko\OAuth2\Client\Provider\CloudConvert([
    'clientId'     => '{cloud-convert-client-id}',
    'clientSecret' => '{cloud-convert-client-secret}',
    'redirectUri'  => 'https://example.com/callback-url',
    'sandbox'      => false, // optional
]);

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;
}

// Check given state against previously stored one to mitigate CSRF attack
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

// Try to get an access token (using the authorization code grant)
$token = $provider->getAccessToken('authorization_code', [
    'code' => $_GET['code']
]);

// Optional: Now you have a token you can look up a users profile data
try {
    // We got an access token, let's now get the user's details
    $user = $provider->getResourceOwner($token);

    // Use these details to create a new profile
    printf('Hello %s!', $user->getNickname());
} catch (Exception $e) {
    // Failed to get user details
    exit('Oh dear...');
}

// Use this to interact with an API on the users behalf
echo $token->getToken();
}
```

### Managing Scopes

When creating your CloudConvert authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['user.read','task.read'], // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://cloudconvert.com/api/v2#authentication).

- user.read
- user.write
- task.read 
- task.write
- webhook.read
- webhook.write

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/osavchenko/oauth2-cloudconvert/blob/master/LICENSE) for more information.
