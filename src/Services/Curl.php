<?php


namespace beinmedia\payment\Services;


use GuzzleHttp\Client;

class Curl
{
    public function postCurl(String $url,String $data,String $api_Key, $formData = false){

        $curl = curl_init();
        $contentType = $formData ? 'multipart/form-data': 'application/json';
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $api_Key",
                "content-type: $contentType"
            ),
        ));

        $result=new \stdClass();
        $result->response=curl_exec($curl);
        $result->err = curl_error($curl);
        curl_close($curl);

        return $result;
    }

    public function getCurl(String $url,String $api_Key){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "{}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $api_Key",
                "content-type: application/json"
            ),
        ));

        $result=new \stdClass();
        $result->response=curl_exec($curl);
        $result->err = curl_error($curl);
        curl_close($curl);

        return $result;
    }

    public function notifyUser($data,$url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data
        ));

        $result = new \stdClass();
        $result->response = curl_exec($curl);
        $result->err = curl_error($curl);
        curl_close($curl);

        return $result;
    }

    public function filePostCurl($data, String $api_Key)
    {
        $client = new Client(['base_uri' => 'https://api.tap.company']);
        $response = $client->post('/v2/files', [
            'headers' => [
                'Authorization' => "Bearer $api_Key"
            ],
            'multipart' => [
                [
                    'name' => 'purpose',
                    'contents' => $data->purpose
                ],
                [
                    'name' => 'title',
                    'contents' => $data->title
                ],
                [
                    'name' => 'expires_at',
                    'contents' => $data->expires_at
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($data->file, 'r'),
                    'headers' => ['Content-Type' => mime_content_type($data->file)]

                ],
                [
                    'name' => 'file_link_create',
                    'contents' => $data->file_link_create
                ]
            ]
        ]);
        return json_decode($response->getBody()->getContents(),true);
    }
}
