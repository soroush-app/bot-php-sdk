<?php

namespace Soroush;

use GuzzleHttp;

class Client
{
    const RETRY_DEFAULT_MS = 3000;
    const END_OF_MESSAGE = "/\n\n/";

    private $_baseDomain = 'https://bot.sapp.ir/';
    private $_getMessageURL = '/getMessage';
    private $_uploadFileURL = '/uploadFile';
    private $_downLoadFileURL = '/downloadFile/';
    private $_sendMessageURL = '/sendMessage';
    protected $_token = "";
    /** @var  GuzzleHttp\Client */
    private $guzzlehttpclient;
    /** @var  string - last received message id */
    private $lastId;
    /** @var GuzzleHttp\Psr7\Response */
    private $response;
    /** @var  int - reconnection time in milliseconds */
    private $retry = self::RETRY_DEFAULT_MS;
    /** @var int - HTTP Request Timeout (seconds) */
    private $timeout = 5;


    public function __construct($token = "")
    {
        if (!empty($token)) $this->_token = $token;
        $this->guzzlehttpclient = new GuzzleHttp\Client([
            'base_uri' => $this->getBaseDomainURL()
        ]);
    }

    public function setToken($token)
    {
        $this->_token = strval($token);
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function getToken()
    {
        return $this->_token;
    }

    private function getBaseDomainURL()
    {
        return $this->_baseDomain;
    }

    private function getGetMessageURL()
    {
        if (empty($this->_token)) throw new \Exception("Invalid Bot Token", 11);
        return 'v2/'.$this->getToken() . $this->_getMessageURL;
    }

    private function getSendMessageURL()
    {
        if (empty($this->_token)) throw new \Exception("Invalid Bot Token", 11);
        return $this->getToken() . $this->_sendMessageURL;
    }

    private function getUploadFileURL()
    {
        if (empty($this->_token)) throw new \Exception("Invalid Bot Token", 11);
        return $this->getToken() . $this->_uploadFileURL;
    }

    private function getDownloadFileURL($file_id)
    {
        if (empty($this->_token)) throw new \Exception("Invalid Bot Token", 11);
        if (empty($file_id)) throw new \Exception("Invalid File ID", 12);
        return $this->getToken() . $this->_downLoadFileURL . $file_id;
    }

    private function connect()
    {

        $headers = [
            'Content-Type' => 'text/event-stream',
            'Accept' => 'text/event-stream',
        ];
        if ($this->lastId) {
            $headers['Last-Event-ID'] = $this->lastId;
        }
        $this->response = $this->guzzlehttpclient->request('GET', $this->getGetMessageURL(), [
            'stream' => true,
            'headers' => $headers
        ]);
    }

    /**
     * @return \Generator
     * @throws \Exception
     */
    public function getMessages()
    {
        if (!$this->response) $this->connect();

        if (!$this->response) throw new \Exception("Cannot Connect to Bot Server !", 10);
        if ($this->response->getStatusCode() != 200) throw new \Exception("Cannot Connect to Bot Server !, Error: " . $this->response->getStatusCode() . ' ' . $this->response->getReasonPhrase(), 10);
        $buffer = '';
        $body = $this->response->getBody();
        while (true) {
            // if server close connection - try to reconnect
            if ($body->eof()) {
                // wait retry period before reconnection
                sleep($this->retry / 1000);
                $this->connect();
                // clear buffer since there is no sense in partial message
                $buffer = '';
            }

            $buffer .= $body->read(1);
            if (preg_match(self::END_OF_MESSAGE, $buffer)) {
                $parts = preg_split(self::END_OF_MESSAGE, $buffer, 2);

                $rawMessage = $parts[0];
                $remaining = $parts[1];

                $buffer = $remaining;

                $event = Event::parse($rawMessage);

                // if message contains id set it to last received message id
                if ($event->getId()) {
                    $this->lastId = $event->getId();
                }

                // take into account server request for reconnection delay
                if ($event->getRetry()) {
                    $this->retry = $event->getRetry();
                }

                yield $event;
            }
        }
    }

    private function get_current_milliseconds()
    {
        $time = number_format(microtime(true), 3, '', '');
        return $time;
    }

    /**
     * @param $data
     * @return array
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function sendRAW($data)
    {
        if (empty($data['time'])) $data['time'] = $this->get_current_milliseconds();
        try {

            $req = $this->guzzlehttpclient->request('POST', $this->getSendMessageURL(), [
                'json' => $data,
                'timeout' => $this->timeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $response = strval($req->getBody());

            if ($response) {
                $response_json = json_decode($response, true);
                if (!empty($response_json['resultCode'])) {
                    if ($response_json['resultCode'] == 200) {
                        return [false, "OK"];
                    } else {
                        return [true, (isset($response_json['resultMessage']) ? $response_json['resultMessage'] : "Unknown Error")];
                    }
                } else {
                    return ["Invalid Response", false];
                }
            }

            return ["Invalid Request", false];

        } catch (\Exception $e) {
            return [$e->getMessage(), false];
        }
    }

    /**
     * @param $to
     * @param $body
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendText($to, $body, $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'TEXT',
            'to' => $to,
            'body' => $body,
            'time' => $time,
        ];

        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $caption
     * @param $image_file_id
     * @param $image_file_name
     * @param $image_file_size
     * @param null $image_width
     * @param null $image_height
     * @param null $thumbnail_file_id
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendImage($to, $caption, $image_file_id, $image_file_name, $image_file_size, $image_width = null, $image_height = null, $thumbnail_file_id = null, $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'FILE',
            'fileType' => 'IMAGE',
            'fileUrl' => $image_file_id,
            'fileName' => $image_file_name,
            'fileSize' => strval($image_file_size),
            'imageWidth' => "0",
            'imageHeight' => "0",
            'thumbnailUrl' => "",
            'to' => $to,
            'body' => $caption,
            'time' => $time,
        ];

        if (intval($image_width) AND intval($image_height)) {
            $data['imageWidth'] = intval($image_width);
            $data['imageHeight'] = intval($image_height);
        }

        if ($thumbnail_file_id) {
            $data['thumbnailUrl'] = strval($thumbnail_file_id);
        }
        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $caption
     * @param $image_file_id
     * @param $image_file_name
     * @param $image_file_size
     * @param null $image_width
     * @param null $image_height
     * @param null $thumbnail_file_id
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendGIF($to, $caption, $image_file_id, $image_file_name, $image_file_size, $image_width = null, $image_height = null, $thumbnail_file_id = null, $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'FILE',
            'fileType' => 'GIF',
            'fileUrl' => $image_file_id,
            'fileName' => $image_file_name,
            'fileSize' => strval($image_file_size),
            'imageWidth' => "0",
            'imageHeight' => "0",
            'thumbnailUrl' => "",
            'to' => $to,
            'body' => $caption,
            'time' => $time,
        ];

        if (intval($image_width) AND intval($image_height)) {
            $data['imageWidth'] = intval($image_width);
            $data['imageHeight'] = intval($image_height);
        }

        if ($thumbnail_file_id) {
            $data['thumbnailUrl'] = strval($thumbnail_file_id);
        }
        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $caption
     * @param $video_file_id
     * @param $video_file_name
     * @param $video_file_size
     * @param $video_duration_in_milliseconds
     * @param int $video_width
     * @param int $video_height
     * @param null $thumbnail_file_id
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendVideo($to, $caption, $video_file_id, $video_file_name, $video_file_size, $video_duration_in_milliseconds, $video_width = 0, $video_height = 0, $thumbnail_file_id = null, $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'FILE',
            'fileType' => 'VIDEO',
            'fileUrl' => $video_file_id,
            'fileName' => $video_file_name,
            'fileSize' => strval($video_file_size),
            'thumbnailUrl' => '',
            'thumbnailWidth' => "0",
            'thumbnailHeight' => "0",
            'fileDuration' => $video_duration_in_milliseconds,
            'to' => $to,
            'body' => $caption,
            'time' => $time,
        ];

        if (intval($video_width) AND intval($video_height)) {
            $data['thumbnailWidth'] = intval($video_width);
            $data['thumbnailHeight'] = intval($video_height);
        }

        if ($thumbnail_file_id) {
            $data['thumbnailUrl'] = strval($thumbnail_file_id);
        }

        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $caption
     * @param $file_id
     * @param $file_name
     * @param $file_size
     * @param $file_duration_in_milliseconds
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendPushToTalk($to, $caption, $file_id, $file_name, $file_size, $file_duration_in_milliseconds, $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'FILE',
            'fileType' => 'PUSH_TO_TALK',
            'fileUrl' => $file_id,
            'fileName' => $file_name,
            'fileSize' => strval($file_size),
            'fileDuration' => $file_duration_in_milliseconds,
            'to' => $to,
            'body' => $caption,
            'time' => $time,
        ];

        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $caption
     * @param $file_id
     * @param $file_name
     * @param $file_size
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendAttachment($to, $caption, $file_id, $file_name, $file_size, $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'FILE',
            'fileType' => 'FILE_TYPE_OTHER',
            'fileUrl' => $file_id,
            'fileName' => $file_name,
            'fileSize' => strval($file_size),
            'to' => $to,
            'body' => $caption,
            'time' => $time,
        ];

        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $latitude
     * @param $longitude
     * @param string $caption
     * @param null $keyboard
     * @param null $time
     * @return array
     */
    public function sendLocation($to, $latitude, $longitude, $caption = "", $keyboard = null, $time = null)
    {
        $data = [
            'type' => 'LOCATION',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'to' => $to,
            'body' => $caption,
            'time' => $time,
        ];

        if ($keyboard !== null)
            $data['keyboard'] = $keyboard;

        return $this->sendRAW($data);
    }

    /**
     * @param $to
     * @param $keyboard
     * @param null $time
     * @return array
     */
    public function changeKeyboard($to, $keyboard, $time = null)
    {
        $data = [
            'type' => 'CHANGE',
            'keyboard' => $keyboard,
            'to' => $to,
            'time' => $time,
        ];
        return $this->sendRAW($data);
    }

    /**
     * @param $data
     * @return array
     */
    public function makeKeyboardData($data)
    {
        $keyboard = [];
        if (is_string($data)) {
            $lines = explode(PHP_EOL, $data);
            foreach ($lines as $line) {
                $line_keyboard = [];
                $line_buttons = explode("|", $line);
                foreach ($line_buttons as $line_button) {
                    if ($line_button === "") continue;
                    $line_keyboard[] = [
                        'text' => $line_button,
                        'command' => $line_button
                    ];
                }
                if (count($line_keyboard))
                    $keyboard[] = $line_keyboard;
            }
        } else if (is_array($data)) {
            foreach ($data as $row_id => $row_data) {
                $row_keyboard = [];
                foreach ($row_data as $row_button_data) {
                    if (is_string($row_button_data)) {
                        $button_data = ['text' => $row_button_data, 'command' => $row_button_data];
                    } else if (is_array($row_button_data)) {
                        $button_data = [];
                        if (isset($row_button_data[0]))
                            $button_data = ['text' => strval($row_button_data[0]), 'command' => (!empty($row_button_data[1]) ? $row_button_data[1] : $row_button_data[0])];
                        if (isset($row_button_data['text']))
                            $button_data = ['text' => strval($row_button_data['text']), 'command' => (!empty($row_button_data['command']) ? $row_button_data['command'] : $row_button_data['text'])];
                    }
                    if (count($button_data))
                        $row_keyboard[] = $button_data;
                }
                if (count($row_keyboard))
                    $keyboard[] = $row_keyboard;
            }
        }
        return $keyboard;
    }

    /**
     * @param $fileUrl
     * @param string $fileName
     * @param string $save_file_path
     * @return array
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function downloadFile($fileUrl, $fileName = "", $save_file_path = "")
    {
        if (!$save_file_path) $save_file_path = tempnam(sys_get_temp_dir(), 'SoroushBotFile') . $fileName;
        try {
            $req = $this->guzzlehttpclient->request('GET', $this->getDownloadFileURL($fileUrl), [
                'sink' => $save_file_path,
            ]);

            $response_status_code = $req->getStatusCode();

            if ($response_status_code == 200) {
                return [false, $save_file_path];
            }

            return ["Bad Response : " . $response_status_code . ' ' . $req->getReasonPhrase(), false];

        } catch (\Exception $e) {
            return [$e->getMessage(), false];
        }
    }

    /**
     * @param $filePath
     * @return array
     */
    public function uploadFile($filePath)
    {
        try {
            if (!is_readable($filePath))
                throw new \Exception("Invalid File");

            /**[
             * 'name' => 'file',
             * 'contents' => fopen($filePath, 'r'),
             * 'filename' => basename($filePath),
             * 'Mime-Type' => GuzzleHttp\Psr7\mimetype_from_filename(basename($filePath)),
             * ]*/

            $post_data = [
                'file' => new \CURLFile($filePath, GuzzleHttp\Psr7\mimetype_from_filename(basename($filePath)), basename($filePath))
            ];

            $ch = curl_init($this->getBaseDomainURL() . $this->getUploadFileURL());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // <-- raw data here hm?
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($ch);
            $req_data = curl_getinfo($ch);

            if ($req_data['http_code'] == 200) {
                if ($response) {
                    $response_json = json_decode($response, true);
                    if (!empty($response_json['resultCode'])) {
                        if ($response_json['resultCode'] == 200) {
                            if (!empty($response_json['fileUrl']))
                                return [false, $response_json['fileUrl']];
                            return ["Unknown Upload Error", false];
                        } else {
                            return [(isset($response_json['resultMessage']) ? $response_json['resultMessage'] : "Unknown Error"), false];
                        }
                    } else {
                        return ["Invalid Response", false];
                    }
                }
                return ["Bad Response", false];
            }

            return ["Bad Response : " . $req_data['http_code'] . ' HTTP Error Code', false];

        } catch (\Exception $e) {

        }
    }


}
