<?php

require("config.php");

function make_array_storage($obj_list)
{
  $arr_return = array();
  foreach($obj_list as $obj){
    $arr_return[$obj->id] = $obj->name;
  }
  return $arr_return;
}


function curlWrap($url, $json, $action)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
	curl_setopt($ch, CURLOPT_URL, ZDURL.$url);
	curl_setopt($ch, CURLOPT_USERPWD, ZDUSER."/token:".ZDAPIKEY);
	switch($action){
		case "POST":
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			break;
		case "GET":
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			break;
		case "PUT":
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		default:
			break;
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));	curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$output = curl_exec($ch);
	curl_close($ch);
	$decoded = json_decode($output);
	return $decoded;
}


/* set the default timezone to west coast so php knows where we are!
 * this will also keep us from getting notices later on. */

date_default_timezone_set('America/Los_Angeles');


/* Let's set the name of the output filename. */

$myFile = "tickets.csv";

/* In order to save bandwidth, we are going to save each assignee id, requester id, 
 * and organization id in an array. That way we don't look up anything twice. */

$assignee_list = array();
$requester_list = array();
$organization_list = array();

/* We need to find out if we passed in command line parameters. argc stores
 * the count of arguments (hence ARGument Count is argc) and argv is the 
 * variables passed in. Start at 1 (the first argument) and count up for
 * each one after. */

if($argc > 1){
  $view = $argv[1];
}else{
  $view = '30194956';
}

/* In order to write to a file you need to reference a tool called a file handler. A
 * a file handler is stored in a variable so that you can refer back to it when needed.
 * We create our file handler below. */

$fh = fopen($myFile, 'w');

/* Here we create an array (basically a numbered list) of fields we are going to use. These are
 * basically the header rows under which all the data gets put. It has to match with data we'll 
 * grab later on. */

$keys = array('id', 'requester_last_updated',  'assignee', 'requester', 'organization', 'subject', 'url', 'satisfaction_rating', 'satisfaction_comment');


fputcsv($fh, $keys, ",", "\"");

$counter = 0;
$decoded_alpha = curlWrap('/views/'.$view.'/execute.json?include=users,organizations', null, 'GET');
if($decoded_alpha->count > 100){ $pages = $decoded_alpha->count / 100; $pages = ceil($pages); }else{ $pages = 1; }
for($i = 1; $i <= $pages+1; $i++){
  if($i > $pages){break;}
  if($i > 1 && $pages > 1){
    $decoded_alpha = curlWrap('/views/'.$view.'/execute.json?include=users,organizations&page='.$i, null, 'GET');
  }
  $decoded = $decoded_alpha->rows;
  echo "Processing Page: ".$i." of ".$pages." pages.\n";
  print "Starting Pull\n";
  $assignee_list = make_array_storage($decoded_alpha->users);
  $requester_list = make_array_storage($decoded_alpha->users);
  $organization_list = make_array_storage($decoded_alpha->users);
  foreach($decoded as $result){
    $counter++;
    print "\n\nRecord: ".$counter."\n";
    print "Assignee: ".$result->assignee."\n";
    if(!array_key_exists($result->assignee, $assignee_list)){
      print "Looking up assignee ".$result->assignee."\n";
      $assignee = curlWrap('/users/'.$result->assignee.'.json', null, "GET");
      $assignee_list[$result->assignee] = $assignee->user->name;
      $cur_assignee = $assignee->user->name;
      print "Found: ".$assignee->user->name."\n";
    }else{
      print "Assignee: ".$assignee_list[$result->assignee]."\n";
      $cur_assignee = $assignee_list[$result->assignee];
    }
    print "Requester: ".$result->requester."\n";
    if(!array_key_exists($result->requester, $requester_list)){
      print "Looking up requester ".$result->requester."\n";
      $requester = curlWrap('/users/'.$result->requester.'.json', null, "GET");
      $requester_list[$result->requester] = $requester->user->name;
      $cur_requester = $requester->user->name;
      print "Found: ".$requester->user->name."\n";
    }else{
      $cur_requester = $requester_list[$result->requester];
      print "Requester: ".$requester_list[$result->requester]."\n";
    }
    if(is_null($result->organization)){
      print "No organization\n";
      $cur_organization = "";
    }else{
      print "organization: ".$result->organization."\n";
      if(!array_key_exists($result->organization, $organization_list)){
        print "Looking up organization ".$result->organization."\n";
        $organization = curlWrap('/organizations/'.$result->organization.'.json', null, "GET");
        $organization_list[$result->organization] = $organization->organization->name;
        $cur_organization = $organization->organization->name;
        print "Found: ".$organization->organization->name."\n";
      }else{
        $cur_organization = $organization_list[$result->organization];
        print "Organization: ".$organization_list[$result->organization]."\n";
      }
    }
    $ticket = curlWrap('/tickets/'.$result->ticket->id.'.json', null, "GET");
    $result2 = (array) $ticket->ticket;
    $ts = $result->requester_updated_at;
    $time = explode("T", $ts);
    $date = $time[0];
    $time = $time[1];
    $time = str_replace("Z", "", $time);
    $baseTimezone = new DateTimeZone('Zulu');
    $userTimezone = new DateTimeZone('America/Los_Angeles');
    $myDateTime = new DateTime($date." ".$time, $baseTimezone);
    $offset = $userTimezone->getOffset($myDateTime);
    $realDate = date('Y-m-d H:i', $myDateTime->format('U'));
    $result3 = array($result2['id'], $realDate,  $cur_assignee, $cur_requester, $cur_organization, $result2['subject'], DOMAIN.'/tickets/'.$result2['id'], $result2['satisfaction_rating']->score, $result2['satisfaction_rating']->comment);
    fputcsv($fh, $result3, ",", "\"");
  }
}
fclose($fh);
?>
