<?php

/**
 *
 * @property string $apiKey
 * @property string $fromEmail
 * @property string $fromName
 * @property stdClass $usersInfo
 * @property string $base
 * @property array $errors
 */
class YiiMandrill extends CApplicationComponent
{
    public $apiKey = '';

    public $fromEmail = '';

    public $fromName = '';

    private $usersInfo;

    private $base = 'http://mandrillapp.com/api/1.0';

    public $errors = array();

    public function init()
    {
        parent::init();

        if (!$this->testConnection()){
            $this->errors[] = 'Invalid API key';
        }

        $this->usersInfo = $this->get('/users/info');
    }

    /**
     * Test connection to Mandrill (validate api key)
     *
     * @return bool
     */
    public function testConnection(){
        return $this->get('/users/ping') === 'PONG!';
    }

    /*
     * Send email
     *
     * Example:
     *   $data = array('text' => 'text', 'subject' => 'subject', 'to_email' => 'gregurco.vlad@gmail.com',);
     *   Yii::app()->yiiMandrill->sendMessage($data);
     *
     * @param $data
     * @return mixed
     */
    public function sendMessage($data){
        $request = array(
            'message' => array(
                'text' => $data['text'],
                'subject' => $data['subject'],
                'from_email' => array_key_exists('from_email', $data) ? $data['from_email'] : $this->fromEmail,
                'from_name' => array_key_exists('from_name', $data) ? $data['from_name'] : $this->fromName,
                'to' => array(
                    array(
                        'email' => $data['to_email'],
                        'type' => 'to'
                    )
                ),
            )
        );

        $result = $this->get('/messages/send', $request);

        return $result; // "sent", "queued", "scheduled", "rejected", or "invalid"
    }

    /**
     * Send request to mandrill api with json encoded data
     *
     * @param $url
     * @param array $params
     * @return mixed
     */
    public function get($url, $params=array()){
        $params['key'] = $this->apiKey;

        $json = json_encode($params);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, "{$this->base}{$url}.json");
        curl_setopt($ch,CURLOPT_POST,count($params));
        curl_setopt($ch,CURLOPT_POSTFIELDS,$json);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json)));

        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result);

        return is_null($decoded) ? $result : $decoded;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getUserInfoProperty($key){
        if (!empty($this->usersInfo) && isset($this->usersInfo->$key)){
            return $this->usersInfo->$key;
        }
    }
}