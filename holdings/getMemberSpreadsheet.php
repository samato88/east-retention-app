<?php
require __DIR__ . '/../vendor/autoload.php';

/* get data from eastmemberdatabase spreadsheet - oclc symbols and oc contacts */
/*
if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
*/
/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */

function getClient()
{
    //*************************************************************//
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API PHP Quickstart');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    $host = gethostname();
    if ( preg_match("/localhost/", $host) ) {
        //**** this used locally - and might need to refresh token with cli > php getMemberSpreadsheet.dist.php (run in same dir as getOCLCHoldings)
        //echo "<h4>localhost</h4>";
        $client->setHttpClient(new GuzzleHttp\Client(['verify'=>'ca-bundle.crt']));
        $client->setAuthConfig('credentials.json');
    }
    else { //****** this used by cloud *****
        $client->useApplicationDefaultCredentials();
    }
    //*************************************************************//



    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }

    return $client;
}

function getSpreadsheet() {

// Get the API client and construct the service object.
    $client = getClient();
    $service = new Google_Service_Sheets($client);
    $monographsymbols = "";
    $serialssymbols = "";
    $monographs = array();
    $serials = array();
    $symboltoid = array();
    $ocinfo = array();

    $eastmembersdbSpreadsheetId = '1ceiwwnXj-Gv3W9tXXppAlx2nh4kX0SqM3z_-cDOo1Xg';
    $ocrange = 'OContact!A:C'; // need hash that is inst_id/email name link
    $memrange = 'Members!A:Q'; //need a string of mono members, serials members and hash that is sym/inst_id

    $memresponse = $service->spreadsheets_values->get($eastmembersdbSpreadsheetId, $memrange);
    $memvalues = $memresponse->getValues();

    $ocresponse = $service->spreadsheets_values->get($eastmembersdbSpreadsheetId, $ocrange);
    $ocvalues = $ocresponse->getValues();

    if (empty($ocvalues)) {
        print "No OC found.\n";
    } else {
        foreach ($ocvalues as $ocrow) { // # hash keyed on id for OC contact info in html format
            $ocinfo[$ocrow[0]] = "$ocrow[1] <a href='mailto:$ocrow[2]'>$ocrow[2]</a>"; // 1 is name , 2 is email address,
            //echo $ocinfo[$ocrow[0] ];
        }
    }

    if (empty($memvalues)) {
        print "No data found.\n";
    } else {
        foreach ($memvalues as $row) { //
            $symbols = explode(" ", strtoupper($row[16])); // make an array of symbols $row[16]
            foreach ($symbols as $sym) {
                $symboltoid[$sym] = $row[0]; // lookup table symbol to library_id
            }

            if (preg_match('/Monographs/', $row[11])) {
                array_push($monographs, implode(',', $symbols));
            }
            if (preg_match('/Serials/', $row[11])) {
                array_push($serials, implode(',', $symbols));
            }
            //printf("*%s*, %s\n", $row[11], $row[16]);
        } // end foreach row

        $monographsymbols = implode(',', $monographs); // , delimited string of monograph partner symbols
        $serialssymbols = implode(',', $serials);
        //echo $monographsymbols . "<br>" ;
        //echo $serialssymbols ;
        //foreach ($symboltoid as $key => $value) { echo "<br>$key => $value" ;}

    } // end else got an answer

// need to return $monographsymbols, $serialssymbols, $symboltoid, $ocinfo
   return array($monographsymbols, $serialssymbols, $symboltoid, $ocinfo);
}