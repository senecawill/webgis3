<?php

include ('request_layers.php');

// -------------------------------------------------------------------
// Notes:
// -------------------------------------------------------------------
// 1. Edit app.js to "request" the .php:
//        app.loadMapbook({url: 'mapbook.php'}).then(function() {
//
// 2. Edit the mapbbok.xml to have a substitution key of "{token}":
//        <param name="token" value="{token}"/>


// -------------------------------------------------------------------
// Main:
// -------------------------------------------------------------------

// ------ Open the mapbook for token substitution: ------
$file = '../mapbook.xml';
$hFile = fopen($file, "r");

// ------ Get a new token via cUrl: ------
$token = getToken();
// ------ Send XML header: ------
header('Content-Type: text/xml');
//ob_clean(); // flush the output


//GET LAYER LIST
$mapBookParts = getLayerList();


// ------ Read lines in mapbook, substitute token and layer list, send the lines to the browser: ------
if ($hFile) {
    while (($line = fgets($hFile)) !== false) {
        $modline = str_replace('{token}', $token , $line);
        $modline2 = str_replace('<!-- {EDIT_MAPSOURCES} -->', $mapBookParts[0], $modline);
        $modline3 = str_replace('<!--  {EDIT_CATALOGLAYERS}  -->', $mapBookParts[1], $modline2);
        echo $modline3;
    }
    fclose($hFile);
//    message("Sent file: " . $file );
} else {
    message("Error: Unable to open file: " . $file );
    exit;
}

// -------------------------------------------------------------------
// -------------------------------------------------------------------
function getToken() {
    // curl -X POST
    //      -d username=seneca-tech
    //      -d password=y4aCpm-Xk#
    //      -d referer=www.senecatechnologies.com
    //      -d f=json
    //      https://arcgis.corelogic.com/arcgis/sharing/generateToken

    $url = "https://arcgis.corelogic.com/arcgis/sharing/rest/generateToken";
    $timeout = 5; // set to zero for no timeout
    $post_data = [
        'username' => 'seneca-tech',
        'password' => 'y4aCpm-Xk#',
        'referer'  => 'www.senecatechnologies.com',
        'f'        => 'json'
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  // fix to allow HTTPS connections with incorrect certificates
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
//    curl_setopt ($ch, CURLOPT_ENCODING , "gzip, deflate");

    $response = curl_exec($ch);
    if(curl_error($ch)) {
        message("ERROR: ".curl_error($ch));
    }
    curl_close($ch);
    
    $json = json_decode($response, true);
    if( isset( $json['token'] ) ){
        $token =  $json['token']; // $json->token; //
        message("Token: ".$token);
    } else {
        message("ERROR: ". $response );
    }
    return $token;
}

// -------------------------------------------------------------------
// -------------------------------------------------------------------
function message($message){
// Writes to browser:
// echo $message ."<BR>";
// writes to php_errors.log in the same folder as this php file:
// error_log($message);
//Writes to local file in the same folder as this php file:

      $logfile = fopen("../logs/mapbook_php_logfile.txt","a");
//    date_default_timezone_set("America/Edmonton");

    date_default_timezone_set("America/New_York");
    
    fwrite($logfile, date("Y-m-d H:i:sP") ."\t". $message . "\n");
    fclose($logfile );

}
?>
