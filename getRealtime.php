<?php

// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 'On');
error_reporting(E_ALL);

$username = "salesdata";
$password = "0h2zgmflyiest6wz";
$dbname = "salesdata";

$connection = mysqli_connect('salesdata-cluster.cluster-cfpdnpkp6okk.eu-west-1.rds.amazonaws.com',$username,$password,$dbname);

  if($connection === false) {
        // Handle error - notify administrator, log to a file, show an error screen, etc.
      echo "connectie error";
  }


$analytics = initializeAnalytics();

$profileIds = "1372830";






$secondsperday=86400;

$firstdayofyear=mktime(12,0,0,1,1,2016);
$lastdayofyear=mktime(12,0,0,12,31,2016);

$theday = $firstdayofyear;

for($theday=$firstdayofyear; $theday<=$lastdayofyear; $theday+=$secondsperday) {
    $dayinfo=getdate($theday);

$query = "SELECT * from analytics_profiles WHERE id=1";
$result = mysqli_query($connection,$query);

    while ($row = $result->fetch_assoc()) {

        $results = getResults($analytics, $row["profileid"],date('Y-m-d',$theday)); // profileId

        //print_r($results);

        echo $results->query->endDate . " ";
        echo "404 pagina : " . $results->totalsForAllResults['ga:pageviews'] . "\n";


        $query = "INSERT INTO analytics_results(analytics_profiles_id,date,notfound) VALUES(".$row['profileid'].",'".date('Y-m-d',$theday)."',".$results->totalsForAllResults['ga:pageviews'].") ON DUPLICATE KEY UPDATE notfound=".$results->totalsForAllResults['ga:pageviews'];
        echo $query;
        mysqli_query($connection,$query);
    }

}


//$profile = getFirstProfileId($analytics);


function initializeAnalytics()
{
  // Creates and returns the Analytics Reporting service object.

  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = __DIR__ . '/service-account-credentials.json';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_Analytics($client);

  return $analytics;
}

function getFirstProfileId($analytics) {
  // Get the user's first view (profile) ID.

  // Get the list of accounts for the authorized user.
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    // Get the list of properties for the authorized user.
    $properties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

    if (count($properties->getItems()) > 0) {
      $items = $properties->getItems();
      $firstPropertyId = $items[0]->getId();

      // Get the list of views (profiles) for the authorized user.
      $profiles = $analytics->management_profiles
          ->listManagementProfiles($firstAccountId, $firstPropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();

        // Return the first view (profile) ID.
        return $items[0]->getId();

      } else {
        throw new Exception('No views (profiles) found for this user.');
      }
    } else {
      throw new Exception('No properties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getResults($analytics, $profileId, $date) {
  // Calls the Core Reporting API and queries for the number of sessions
  // for the last seven days.


//   return $analytics->data_ga->get(
//       'ga:' . $profileId,
//       '7daysAgo',
//       'today',
//       'ga:pageviews');

  $optParams = array(
      'dimensions' => 'ga:pageTitle',
      'metrics' => 'ga:pageviews',
//      'sort' => '-ga:sessions',
      'filters' => 'ga:pageTitle=@404',
      'max-results' => '25');

  return $analytics->data_ga->get(
      'ga:' . $profileId,
      $date,
      $date,
      'ga:sessions',
      $optParams);


}

function printResults($results) {
  // Parses the response from the Core Reporting API and prints
  // the profile name and total sessions.
  if (count($results->getRows()) > 0) {

    // Get the profile name.
    $profileName = $results->getProfileInfo()->getProfileName();

    // Get the entry for the first entry in the first row.
    $rows = $results->getRows();
    
	$sessions = $rows[0][0];

    // Print the results.
    print "First view (profile) found: $profileName\n";
    print "Total sessions: $sessions\n";
  } else {
    print "No results found.\n";
  }
}

