<?php
defined('MOODLE_INTERNAL') || die();

require_once('lib.php');

/**
 * Obtain and initialize the secret, adminsecret and partner_id from the server
 */
function kaltura_init_hosted_account($email, $password) {
    try {


        $config_obj = new KalturaConfiguration(0);
        $config_obj->serviceUrl = KalturaHelpers::getKalturaServerUrl();
        $config_obj->setLogger(new KalturaLogger());

        $kClient = new KalturaClient($config_obj);

        $ksId = $kClient->adminUser->login($email, $password);

        $kClient->setKs($ksId);

        $kInfo = $kClient->partner->getInfo();

        // Check if these values already exist in the config table
        $secret         = get_config(KALTURA_PLUGIN_NAME, 'kaltura_secret');
        $adminsecret    = get_config(KALTURA_PLUGIN_NAME, 'kaltura_adminsecret');
        $partnerid      = get_config(KALTURA_PLUGIN_NAME, 'kaltura_partner_id');

        $setsecret      = (empty($secret) or (0 != strcmp($secret, $kInfo->secret)));
        $setadminsecret = (empty($adminsecret) or (0 != strcmp($adminsecret, $kInfo->adminSecret)));
        $setpartnerid   = (empty($partnerid) or (0 != strcmp($partnerid, $kInfo->id)));

        if ($setsecret or $setadminsecret or $setpartnerid) {

            $data = new stdClass;

            // Update everything to be safe

            set_config('kaltura_secret', $kInfo->secret, KALTURA_PLUGIN_NAME);

            set_config('kaltura_adminsecret', $kInfo->adminSecret, KALTURA_PLUGIN_NAME);

            set_config('kaltura_partner_id', $kInfo->id, KALTURA_PLUGIN_NAME);

        }

        return true;
    } catch(Exception $exp) {
        return false;
    }
}

/**
 * Get the username and password used for the connection type
 */
function kaltura_get_credentials() {

    $uri = get_config(KALTURA_PLUGIN_NAME, 'kaltura_uri');

    $login = false;
    $password = false;

    $login = get_config(KALTURA_PLUGIN_NAME, 'kaltura_login');
    $password = get_config(KALTURA_PLUGIN_NAME, 'kaltura_password');

    return array($login, $password);
}

/**
 * Login to admin account
 */
function kaltura_login() {

    list($username, $password) = kaltura_get_credentials();

    if (empty($username) or empty($password)) {
        return false;
    }

    $kClient = new KalturaClient(KalturaHelpers::getServiceConfiguration());

    $ksId = $kClient->adminUser->login($username, $password);

    $kClient->setKs($ksId);

    return $kClient;

}

/**
 * Retrieve a list of all the custom players available to the account
 */

function kaltura_get_players() {

    $kClient = kaltura_login();

    $resultObject = $kClient->uiConf->listAction(null, null);

    return $resultObject;
}

/**
 * This method take an video entry id of type "mix" and if the video
 * duration is 0 seconds and finds the "video" type that is related to it.
 *
 * If the entry id is not of type "mix" then the same entry id is returned
 *
 * Resolves KMI-36
 *
 * @param  $entryid - entry id of the video
 */
function kaltura_get_video_type_entry($entryid) {

    $kaltura_client = kaltura_login();
    $object = new stdClass();

    //$entry = $kaltura_client->mixing->get('');
    $entry = $kaltura_client->baseEntry->get($entryid);

    $object->entryid = $entryid;
    $object->type    = $entry->type;

    // If we encounter a entry of type "mix", we must find the regular "video" type and display that for playback
    if (KalturaEntryType::MIX == $entry->type and
        0 >= $entry->duration) {

        // This call returns an array of "video" type entries that exist in the "mix" entry
        $media_entries = $kaltura_client->mixing->getReadyMediaEntries($entryid);

        if (!empty($media_entries)) {
            // Take the first "video" type.  If there is more than one I have no idea what should be done.
            $object->entryid    = $media_entries[0]->id;
            $object->type       = $media_entries[0]->type;

        } else {
            $object->entryid    = 0;
            $object->type       = '1';
        }
    }

    return $object;
}
?>