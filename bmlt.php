<?php
	ini_set('display_errors', 1); error_reporting(E_ALL);

	require 'vendor/autoload.php';
	
	// Setup AWS Polly & CloudConvert
	
	use Aws\Polly\PollyClient;
	use \CloudConvert\Api;

	$config = [
		'version' => 'latest',
		'region' => 'us-east-1',
		'credentials' => [
			'key' => getenv("AWS_KEY"),
			'secret' => getenv("AWS_SECRET"),
		]
	];
	try {$client = new PollyClient($config);}
	catch(Exception $e) {print_r($e); exit;}
	
	$cloudconvert = new Api(getenv("CC_KEY"));
	
	
	// Retrieve Meetings from BMLT
	
	$meetings = json_decode(curl_get("http://bmlt.newyorkna.org/main_server/client_interface/json/?switcher=GetSearchResults&services=1051&data_field_key=weekday_tinyint,start_time,meeting_name,location_text,location_street,location_province,location_info,location_municipality&sort_keys=weekday_tinyint,start_time"));
    
    
    // Format Meeting info into sentences
    
    foreach($meetings as $meeting){
	    $time = strtotime("1/1/2000 {$meeting->start_time}");
	    if(date("i", $time) == "00"){ $o = "At " . date("g a", $time); }
	    	else { $o = "At " . date("g:i a", $time); }
	    if(strpos( strtolower($meeting->meeting_name), "the") !== 0){ $o .= " the";}
	    $o .= " {$meeting->meeting_name}";
	    if(!strpos($meeting->meeting_name, "Group")){ $o .= " group";}
	    $o .= " meets";
	    if($meeting->location_text){ $o .= " at the {$meeting->location_text}"; }
	    $o .= " at {$meeting->location_street} in {$meeting->location_municipality}, {$meeting->location_province}. ";
	    if($meeting->location_info){ $o .= " {$meeting->location_info}."; }
	    $text[$meeting->weekday_tinyint][] = $o;
    }
    
    
    // Convert sentences to mp3 with AWS Polly, then convert mp3 to wav with rest7
    
    $downame = [1 => "sunday", 2 => "monday", 3 => "tuesday", 4 => "wednesday", 5 => "thursday", 6 => "friday", 7 => "saturday"];
    foreach ($text as $dow => $day){
	    
		$speech = [
		    "Text" => str_replace(
		    	["NA","NH","VT","MA","BFHC"],
		    	["N.A.","New Hampshire","Vermont","Massachussetts","Bellows Falls Health Center"],
		    	implode(chr(13), $day)),
		    'OutputFormat' => 'mp3',
			'TextType' => 'text',
			'VoiceId' => 'Amy'
	    ];
	    $response = $client->synthesizeSpeech($speech);
		file_put_contents("/root/bmlt/audio/{$downame[$dow]}.mp3", $response['AudioStream']);
		
		$wavfile = "/var/lib/asterisk/sounds/en/custom/{$downame[$dow]}meetings.wav";
		
		$cloudconvert->convert([
				"inputformat" => "mp3",
				"outputformat" => "wav",
				"input" => "upload",
				"preset" => "iEkov1jfOM",
				"file" => fopen("/root/bmlt/audio/{$downame[$dow]}.mp3", 'r'),
			])
			->wait()
			->download($wavfile);
		chown($wavfile, 'asterisk');
		chgrp($wavfile, 'asterisk');
		chmod($wavfile, 0664);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    
    function curl_get($url)
    {
	    $ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$data = curl_exec($ch); 
		
		curl_close($ch);
		return $data;
    };
