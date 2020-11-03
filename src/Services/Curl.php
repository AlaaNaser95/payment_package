<?php


namespace beinmedia\payment\Services;


class Curl
{
    public function postCurl(String $url,String $data,String $api_Key){

        $curl = curl_init();

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
                "content-type: application/json"
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

    public function deleteCurl(String $url,String $api_Key){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
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
}
