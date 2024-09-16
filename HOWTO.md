# Privacy Portal SSO

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Welcome privacy-conscious users to your website and/or email newsletter, with features like "Sign In With Privacy Portal"
and "Subscribe Anonymously".

## Description

This plugin allows users to authenticate using "Sign In With Privacy Portal". After obtaining consent, an existing user
is automatically logged into WordPress, while new users are created in the WordPress database. [Privacy Portal](https://privacyportal.org)
protects user privacy by generating email aliases (AKA Privacy Alias) that relay emails to their personal email addresses,
keeping them private.

Similarly, it allows visitors to anonymously subscribe your newsletter without ever exposing their personal emails to your site. It provides a "Subscribe Anonymously" button that authenticates visitors through Privacy Portal and enrolls them in your newsletter using an automatically generated email alias.

Much of the documentation can be found on the Settings > Privacy Portal SSO dashboard page.

## Table of Contents

- [Installation](#installation)
- [Frequently Asked Questions](#frequently-asked-questions)
    - [What is the Redirect URI?](#what-is-the-redirect-uri)
- [Configuration Environment Variables/Constants](#configuration-environment-variables-constants)
- [Hooks](#hooks)
    - [Filters](#filters)
        - [pp-sso-alter-request](#pp-sso-alter-request)
        - [pp-sso-login-button-text](#pp-sso-login-button-text)
        - [pp-sso-auth-url](#pp-sso-auth-url)
        - [pp-sso-user-login-test](#pp-sso-user-login-test)
        - [pp-sso-user-creation-test](#pp-sso-user-creation-test)
        - <del>[pp-sso-alter-user-claim](#pp-sso-alter-user-claim)</del>
        - [pp-sso-alter-user-data](#pp-sso-alter-user-data)
        - [pp-sso-settings-fields](#pp-sso-settings-fields)
    - [Actions](#actions)
        - [pp-sso-user-create](#pp-sso-user-create)
        - [pp-sso-user-update](#pp-sso-user-update)
        - [pp-sso-update-user-using-current-claim](#pp-sso-update-user-using-current-claim)
        - [pp-sso-redirect-user-back](#pp-sso-redirect-user-back)


## Installation

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin
1. Visit Settings > Privacy Portal SSO and configure to meet your needs


## Frequently Asked Questions

### What is the Redirect URI?

Privacy Portal's OAUTH2 servers require a whitelist of redirect URIs for security purposes. When using Privacy Portal SSO,
there are two redirect URIs that you need to add depending on the features you're looking to use. You must add both redirect
URIs in your app configuration if you'd like to use both "Sign In With Privacy Portal" and "Subscribe Anonymously".

You can find the redirect URIs on the plugin's settings page.

## Configuration Environment Variables/Constants

- Client ID: `OIDC_CLIENT_ID`
- Client Secret Key: `OIDC_CLIENT_SECRET`
- Create user if they do not exist: `OIDC_CREATE_IF_DOES_NOT_EXIST` (boolean)
- Link existing user: `OIDC_LINK_EXISTING_USERS` (boolean)
- Redirect user back to origin page: `OIDC_REDIRECT_USER_BACK` (boolean)

## Hooks

This plugin provides a number of hooks to allow for a significant amount of customization of the plugin operations from 
elsewhere in the WordPress system.

### Filters

Filters are WordPress hooks that are used to modify data. The first argument in a filter hook is always expected to be
returned at the end of the hook.

WordPress filters API - [`add_filter()`](https://developer.wordpress.org/reference/functions/add_filter/) and 
[`apply_filters()`](https://developer.wordpress.org/reference/functions/apply_filters/).

Most often you'll only need to use `add_filter()` to hook into this plugin's code.

#### `pp-sso-alter-request`

Hooks directly into client before requests are sent to the OpenID Server.

Provides 2 arguments: the request array being sent to the server, and the operation currently being executed by this 
plugin.

Possible operations:

- get-authentication-token
- refresh-token
- get-userinfo

```
add_filter('pp-sso-alter-request', function( $request, $operation ) {
    if ( $operation == 'get-authentication-token' ) {
        $request['some_key'] = 'modified value';
    }
    
    return $request;
}, 10, 2);
```

#### `pp-sso-login-button-text`

Modify the login button text. Default value is `__( 'Sign In With Privacy Portal' )`.

Provides 1 argument: the current login button text.

```
add_filter('pp-sso-login-button-text', function( $text ) {
    $text = __('Login to my super cool IDP server');
    
    return $text;
});
```

#### `pp-sso-auth-url`

Modify the authentication URL before presented to the user. This is the URL that will send the user to the IDP server 
for login.

Provides 1 argument: the plugin generated URL.

```
add_filter('pp-sso-auth-url', function( $url ) {
    // Add some custom data to the url.
    $url.= '&my_custom_data=123abc';
    return $url;
}); 
```

#### `pp-sso-user-login-test`

Determine whether or not the user should be logged into WordPress.

Provides 2 arguments: the boolean result of the test (default `TRUE`), and the `$user_claim` array from the server.

```
add_filter('pp-sso-user-login-test', function( $result, $user_claim ) {
    // Don't let Terry login.
    if ( $user_claim['email'] == 'terry@example.com' ) {
        $result = FALSE;
    }
    
    return $result;
}, 10, 2);
```

#### `pp-sso-user-creation-test`

Determine whether or not the user should be created. This filter is called when a new user is trying to login and they
do not currently exist within WordPress.

Provides 2 arguments: the boolean result of the test (default `TRUE`), and the `$user_claim` array from the server.

```
add_filter('', function( $result, $user_claim ) {
    // Don't let anyone from example.com create an account.
    $email_array = explode( '@', $user_claim['email'] );
    if ( $email_array[1] == 'example.com' ) {
        $result = FALSE;
    }
    
    return $result;
}, 10, 2) 
```

#### `pp-sso-alter-user-data`

Modify a new user's data immediately before the user is created.

Provides 2 arguments: the `$user_data` array that will be sent to `wp_insert_user()`, and the `$user_claim` from the 
server.

```
add_filter('pp-sso-alter-user-data', function( $user_data, $user_claim ) {
    // Don't register any user with their real email address. Create a fake internal address.
    if ( !empty( $user_data['user_email'] ) ) {
        $email_array = explode( '@', $user_data['user_email'] );
        $email_array[1] = 'my-fake-domain.co';
        $user_data['user_email'] = implode( '@', $email_array );
    }
    
    return $user_data;
}, 10, 2);
```

#### `privacy-portal-sso-settings-fields`

For extending the plugin with a new setting field (found on Dashboard > Settings > Privacy Portal SSO) that the site
administrator can modify. Also useful to alter the existing settings fields.

See `/includes/pp-sso-settings-page.php` for how fields are constructed.

New settings fields will be automatically saved into the wp_option for this plugin's settings, and will be available in 
the `\PP_SSO_Option_Settings` object this plugin uses.

**Note:** It can be difficult to get a copy of the settings from within other hooks. The easiest way to make use of 
settings in your custom hooks is to call 
`$settings = get_option('privacy_portal_sso_settings', array());`.

Provides 1 argument: the existing fields array.

```
add_filter('pp-sso-settings-fields', function( $fields ) {

    // Modify an existing field's title.
    $fields['endpoint_userinfo']['title'] = __('User information endpoint url');
    
    // Add a new field that is a simple checkbox.
    $fields['block_terry'] = array(
        'title' => __('Block Terry'),
        'description' => __('Prevent Terry from logging in'),
        'type' => 'checkbox',
        'section' => 'authorization_settings',
    );
    
    // A select field that provides options.
    
    $fields['deal_with_terry'] = array(
        'title' => __('Manage Terry'),
        'description' => __('How to deal with Terry when he tries to log in.'),
        'type' => 'select',
        'options' => array(
            'allow' => __('Allow login'),
            'block' => __('Block'),
            'redirect' => __('Redirect'),
        ),
        'section' => 'authorization_settings',
    );
    
    return $fields;
});
```
"Sections" are where your setting appears on the admin settings page. Keys for settings sections:

- client_settings
- user_settings
- authorization_settings
- log_settings

Field types:

- text
- checkbox
- select (requires an array of "options")

### Actions

WordPress actions are generic events that other plugins can react to.

Actions API: [`add_action`](https://developer.wordpress.org/reference/functions/add_action/) and [`do_actions`](https://developer.wordpress.org/reference/functions/do_action/)

You'll probably only ever want to use `add_action` when hooking into this plugin.

#### `pp-sso-user-create`

React to a new user being created by this plugin.

Provides 2 arguments: the `\WP_User` object that was created, and the `$user_claim` from the IDP server.

``` 
add_action('pp-sso-user-create', function( $user, $user_claim ) {
    // Send the user an email when their account is first created.
    wp_mail( 
        $user->user_email,
        __('Welcome to my web zone'),
        "Hi {$user->first_name},\n\nYour account has been created at my cool website.\n\n Enjoy!"
    ); 
}, 10, 2);
``` 

#### `pp-sso-user-update`

React to the user being updated after login. This is the event that happens when a user logins and they already exist as 
a user in WordPress, as opposed to a new WordPress user being created.

Provides 1 argument: the user's WordPress user ID.

``` 
add_action('pp-sso-user-update', function( $uid ) {
    // Keep track of the number of times the user has logged into the site.
    $login_count = get_user_meta( $uid, 'my-user-login-count', TRUE);
    $login_count += 1;
    add_user_meta( $uid, 'my-user-login-count', $login_count, TRUE);
});
```

#### `pp-sso-update-user-using-current-claim`

React to an existing user logging in (after authentication and authorization).

Provides 2 arguments: the `WP_User` object, and the `$user_claim` provided by the IDP server.

```
add_action('pp-sso-update-user-using-current-claim', function( $user, $user_claim) {
    // Based on some data in the user_claim, modify the user.
    if ( !empty( $user_claim['wp_user_role'] ) ) {
        if ( $user_claim['wp_user_role'] == 'should-be-editor' ) {
            $user->set_role( 'editor' );
        }
    }
}, 10, 2); 
```

#### `pp-sso-redirect-user-back`

React to a user being redirected after a successful login. This hook is the last hook that will fire when a user logs 
in. It will only fire if the plugin setting "Redirect Back to Origin Page" is enabled at Dashboard > Settings > 
Privacy Portal SSO. It will fire for both new and existing users.

Provides 2 arguments: the url where the user will be redirected, and the `WP_User` object.

```
add_action('pp-sso-redirect-user-back', function( $redirect_url, $user ) {
    // Take over the redirection complete. Send users somewhere special based on their capabilities.
    if ( $user->has_cap( 'edit_users' ) ) {
        wp_redirect( admin_url( 'users.php' ) );
        exit();
    }
}, 10, 2); 
```

### User Meta Data

This plugin stores meta data about the user for both practical and debugging purposes.

* `pp-sso-subject-identity` - The identity of the user provided by the IDP server.
* `pp-sso-last-id-token-claim` - The user's most recent `id_token` claim, decoded and stored as an array.
* `pp-sso-last-user-claim` - The user's most recent `user_claim`, stored as an array.
* `pp-sso-last-token-response` - The user's most recent `token_response`, stored as an array.
