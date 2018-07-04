<?php

require_once 'ASEngine/AS.php';

$provider = @ $_GET['p'];

$token = @ $_GET['token'];

if ( $token == '' || $token == null || $token !== ASSession::get('as_social_token') ) {
    ASSession::destroy('as_social_token');
    die('Wrong social auth token!');
}

if ( $provider == '' || $provider == null )
    die('Wrong provider.');

switch($provider) {
    case 'twitter':
        if ( ! TWITTER_ENABLED ) die ('This provider is not enabled.');
        break;
    case 'facebook':
        if ( ! FACEBOOK_ENABLED ) die ('This provider is not enabled.');
        break;
    case 'google':
        if ( ! GOOGLE_ENABLED ) die ('This provider is not enabled.');
        break;

    default:
        die('This provider is not supported!');
}

require_once 'vendor/hybridauth/Hybrid/Auth.php';

$config = dirname(__FILE__) . '/vendor/hybridauth/config.php';

try {
    $hybridauth = new Hybrid_Auth( $config );

    $adapter = $hybridauth->authenticate( $provider );

    $userProfile = $adapter->getUserProfile();

    if ( $register->registeredViaSocial($provider, $userProfile->identifier) )
    {
        $user = $register->getBySocial($provider, $userProfile->identifier);
        $login->byId($user['user_id']);
        header('Location: index.php');
    }
    else
    {

        $validator = new ASValidator();

        if ( $validator->emailExist($userProfile->email) )
        {
            $user = $register->getByEmail($userProfile->email);
            $register->addSocialAccount($user['user_id'], $provider, $userProfile->identifier);
            $login->byId($user['user_id']);
            header('Location: index.php');
        }
        else
        {
            $user = new ASUser(null);

            $username = str_replace(' ', '', $userProfile->displayName);

            $tmpUsername = $username;

            $i = 0;
            $max = 50;

            while ( $validator->usernameExist($tmpUsername) ) {

                if ( $i > $max )
                    break;

                $tmpUsername = $username . rand(1, 10000);
                $i++;
            }

            if ( $i > $max )
                $tmpUsername = uniqid('user', true);


            $username = $tmpUsername;

            $info = array(
                'email'         => $userProfile->email == null ? '' : $userProfile->email,
                'username'      => $username,
                'password'      => $register->hashPassword(hash('sha512', $register->randomPassword())),
                'confirmed'     => 'Y',
                'register_date' => date('Y-m-d H:i:s')
            );
            $details = array(
                'first_name' => $userProfile->firstName == null ? '' : $userProfile->firstName,
                'last_name'  => $userProfile->lastName == null ? '' : $userProfile->lastName,
                'address'    => $userProfile->address == null ? '' : $userProfile->address,
                'phone'      => $userProfile->phone == null ? '' : $userProfile->phone
            );

            $db->insert('as_users', $info);

            $userId = $db->lastInsertId();

            $details['user_id'] = $userId;

            $db->insert('as_user_details', $details);

            $register->addSocialAccount($userId, $provider, $userProfile->identifier);
            $login->byId($userId);
            header('Location: index.php');
        }

    }
}
catch( Exception $e ) {
    header('Location: login.php');
}


