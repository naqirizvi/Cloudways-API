<?php
//Use this function to contact CW API
/**
 * 
 * @param string $method GET|POST|PUT|DELETE
 * @param string $url relative URL for the call
 * @param string $accessToken Access token generated using OAuth Call
 * @param type $post Optional post data for the call
 * @return object Output from CW API
 */
function callCloudwaysAPI($method, $url, $accessToken, $post = [])
{
    $baseURL = 'https://api.cloudways.com/api/v1';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_URL, $baseURL . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_HEADER, 1);
    //Set Authorization Header
    if ($accessToken) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    }

    //Set Post Parameters
    $encoded = '';
    if (count($post)) {
        foreach ($post as $name => $value) {
            $encoded .= urlencode($name) . '=' . urlencode($value) . '&';
        }
        $encoded = substr($encoded, 0, strlen($encoded) - 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        curl_setopt($ch, CURLOPT_POST, 1);
    }

    $output = curl_exec($ch);

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode != '200') {
        die('An error occurred code: ' . $httpcode . ' output: ' . substr($output, 0, 10000));
    }
    curl_close($ch);
    return json_decode($output);
}
//Fetch Access Token
$tokenResponse = callCloudwaysAPI('POST', '/oauth/access_token', null
    , [
    'email' => 'YOUR_EMAIL_ADDRESS'
    , 'api_key' => 'YOUR_SECRET_API_KEY'
    ]);
$accessToken = $tokenResponse->access_token;

//Fetch Server List
$serverList = callCloudwaysAPI('GET', '/server', $accessToken);

//Create a New server
$addSeverResponse = callCloudWaysAPI('POST', '/server', $accessToken, [
    'cloud' => 'do',
    'region' => 'nyc3',
    'instance_type' => '512MB',
    'application' => 'wordpress',
    'app_version' => '4.6.1',
    'server_label' => 'API Test',
    'app_label' => 'API Test',
    ]);

$operation = $addSeverResponse->server->operations[0];

//Wait for operation to be completed
while ($operation->is_completed == 0) {
    $operation = callCloudWaysAPI('GET', '/operation/' . $operation->id, $accessToken)->operation;
    sleep(30);
}
