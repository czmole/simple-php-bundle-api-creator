<?php

/**
 *		Usage
 *
 *  	$push = new PushNotification();
 *   	$push->send_user_push($user->id, $newMessage->subject);
 *
 * 		Reading material
 * 			https://gist.github.com/prime31/5675017
 * 			https://gist.github.com/joashp/b2f6c7e24127f2798eb2
 * 			http://devgirl.org/2013/07/17/tutorial-implement-push-notifications-in-your-phonegap-application/
 *
 */

class PushNotification
{
    protected $em;

    // Optional parameters
    protected $apple_push_url;
    protected $apple_push_pem;
    protected $apple_push_passphrase;

    /**
     * Initialize the PushNotification service
     * This service handles push notification sender
     *
     */
    public function __construct()
    {
    	$this->setApplePushUrl(getenv('APPLE_URL'));
    	$this->setApplePushPem(getenv('APPLE_PEM'));
    	$this->setApplePushPassphrase(getenv('APPLE_PASSPHRASE'));
    }

    /**
     * Setup the apple push url value as configured in the .env file
     *
     * @param string $apple_push_url
     */
    public function setApplePushUrl($apple_push_url)
    {
        $this->apple_push_url = $apple_push_url;
    }

    /**
     * Setup the apple push pem value as configured in the .env file
     *
     * @param string $apple_push_pem
     */
    public function setApplePushPem($apple_push_pem)
    {
        $this->apple_push_pem = $apple_push_pem;
    }

    /**
     * Setup the apple push passphrase value as configured in the .env file
     *
     * @param string $apple_push_passphrase
     */
    public function setApplePushPassphrase($apple_push_passphrase)
    {
        $this->apple_push_passphrase = $apple_push_passphrase;
    }

    /*
     * Manage push id's, group by os, then under 1000, and send it
     *
     * $data with all push id, and os
     * $message the message that will be sent
     */
    private function manage_push($data, $message)
    {
        // split in to array's google & apple
        $split = self::split_push($data);

        $google = $split['google'];
        $apple  = $split['apple'];

        // send by google
        foreach($google as $google_ids){
            self::send_to_google($google_ids, $message);
        }

        // send by apple
        self::send_to_apple($apple, $message);
    }

    /*
     * Split in 2 arrays by os
     */
    private function split_push($data)
    {
        $split = array('apple' => array(), 'google' => array());

        // divide by os
        foreach($data as $item){

        	if(stripos('iOS', $item['os']) !== false){
                $split['apple'][] = $item['push_id'];
            }elseif(stripos('Android', $item['os']) !== false){
                $split['google'][] = $item['push_id'];
            }
        }

        // group by 900 items
        $split['google'] = array_chunk($split['google'], 900, FALSE);

        return $split;
    }

    /*
     * Send push's to Google
     */
    private function send_to_google($registration_ids, $message)
    {
        //return 0;


        // Set POST variables
        $key = getenv('ANDROID_KEY');
        $url = getenv('ANDROID_URL');

        $pa_data = array();

        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => array("notify" => $message),
        );

        $headers = array(
            'Authorization: key=' . $key,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            return FALSE;
        }

        // Close connection
        curl_close($ch);
        //echo $result;
    }

    /*
     * Send push's to Apple
     */
    private function send_to_apple($ids, $message)
    {
        $apnsHost = $this->apple_push_url;
        $apnsPort = 2195;
        $apnsCert = realpath(dirname(__FILE__) . '/../../') . '/' . $this->apple_push_pem;

        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'default');

        $payload = json_encode($payload);

        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
        stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->apple_push_passphrase);

        foreach($ids as $deviceToken){
            if($deviceToken != null){
                try{
                    $apns = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

                    $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $deviceToken)) . chr(0) . chr(strlen($payload)) . $payload;

                    fwrite($apns, $apnsMessage);

                    //if (isset($apns)) {
                   //     @socket_close($apns);
                   // }

                    fclose($apns);
                } catch (Exception $e) {
                   //echo $e->getMessage();
                }
            }
        }
    }


    /*
     * Send push to device
     */
    public function send_user_push($user_id, $message)
    {
    	// make custom select for push_id
        $data = Device::select(array('*', 'os', 'token AS push_id'))
            ->where('active', '=', '1')
            ->where('user_id', '=', $user_id)
            ->get();

        // call function send push
        self::manage_push($data, $message);
    }


}
