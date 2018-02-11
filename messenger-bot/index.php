<?php
	require "db.php";
	// *** Include the class
	include("resize-class.php");
	$verify_token = ""; // Verify token
	$token = ""; // Page token

	if (file_exists(__DIR__.'/config.php')) {
		$config = include __DIR__.'/config.php';
		$verify_token = $config['verify_token'];
		$token = $config['token'];
	}

	require_once(dirname(__FILE__) . '/vendor/autoload.php');

	use pimax\FbBotApp;
	use pimax\Messages\Message;
	use pimax\Messages\ImageMessage;
	use pimax\UserProfile;
	use pimax\Messages\MessageButton;
	use pimax\Messages\StructuredMessage;
	use pimax\Messages\MessageElement;
	use pimax\Messages\MessageReceiptElement;
	use pimax\Messages\Address;
	use pimax\Messages\Summary;
	use pimax\Messages\Adjustment;

	// Make Bot Instance
	$bot = new FbBotApp($token);
	
	function userRegisteration($con, $id){
		$sql = "SELECT 
					`tbl_users`.`idtbl_users`,
					`tbl_users`.`tbl_fbid`
				FROM
					`rotaract_igenius`.`tbl_users`
				WHERE
					`tbl_users`.`tbl_fbid` = '".$id."';";
		$result = mysqli_query($con, $sql);
		//$idtbl_users = 1;
		if(mysqli_num_rows($result) > 0){			
			while($row = mysqli_fetch_array($result)){
				mysqli_query($con, "INSERT INTO `rotaract_igenius`.`tbl_users` (`tbl_fbid`) VALUES ('".$id."') ON DUPLICATE KEY UPDATE `visit` = `visit` + 1;");
				return $row["idtbl_users"];
			}	
		}else{
			mysqli_query($con, "INSERT INTO `rotaract_igenius`.`tbl_users` (`tbl_fbid`) VALUES ('".$id."') ON DUPLICATE KEY UPDATE `visit` = `visit` + 1;");
			return mysqli_insert_id($con);
		}
	}
	
	function alreadyAsked($fbid, $con){
		$sql1 = "SELECT 
					`tbl_questions`.`idtbl_questions`,
					`tbl_questions`.`tbl_q`,
					`tbl_questions`.`tbl_ans1`,
					`tbl_questions`.`tbl_ans2`,
					`tbl_questions`.`tbl_rightAnswer`
				FROM
					`rotaract_igenius`.`tbl_questions`
				WHERE
					`tbl_questions`.`idtbl_questions` NOT IN
					(
						SELECT 
							`tbl_questions`.`idtbl_questions`
						FROM 
							`rotaract_igenius`.`tbl_users_has_questions`, `rotaract_igenius`.`tbl_questions`, `rotaract_igenius`.`tbl_users`
						WHERE
							`tbl_users_has_questions`.`idtbl_questions` = `tbl_questions`.`idtbl_questions` AND
							`tbl_users_has_questions`.`idtbl_users` = `tbl_users`.`idtbl_users` AND
							`tbl_users`.`tbl_fbid` = '".$fbid."'
					);";
		$result1 = mysqli_query($con, $sql1);
		if(mysqli_num_rows($result1) > 0){				
			while($row1 = mysqli_fetch_array($result1)){
				return $row1;
			}			
		}else{
			return 0;
		}
	}
	
	function askRQuestion($fbid, $con){
		$sql2 = "SELECT 
					`tbl_questions`.`idtbl_questions`
				FROM
					`rotaract_igenius`.`tbl_questions`
				WHERE
					`tbl_questions`.`idtbl_questions` NOT IN
					(
						SELECT 
							`tbl_questions`.`idtbl_questions`
						FROM 
							`rotaract_igenius`.`tbl_users_has_questions`, `rotaract_igenius`.`tbl_questions`, `rotaract_igenius`.`tbl_users`
						WHERE
							`tbl_users_has_questions`.`idtbl_questions` = `tbl_questions`.`idtbl_questions` AND
							`tbl_users_has_questions`.`idtbl_users` = `tbl_users`.`idtbl_users` AND
							`tbl_users`.`tbl_fbid` = '".$fbid."'
					);";
		
		$result2 = mysqli_query($con, $sql2);
		
		$arp = array();
		while($row = mysqli_fetch_array($result2)){
			$arp[] = $row;
		}
		//error_log(print_r($arp, true));
		$d = array_rand($arp);
		error_log(print_r($d, true));
		return $d;
	}
	
	function askQuestion($id, $con){
		$sql2 = "SELECT 
					`tbl_questions`.`idtbl_questions`,
					`tbl_questions`.`tbl_q`,
					`tbl_questions`.`tbl_ans1`,
					`tbl_questions`.`tbl_ans2`,
					`tbl_questions`.`tbl_rightAnswer`
				FROM 
					`rotaract_igenius`.`tbl_questions`
				WHERE
					`tbl_questions`.`idtbl_questions` = '".$id."';";
		
		$result2 = mysqli_query($con, $sql2);
		
		while($row = mysqli_fetch_array($result2)){
			return $row;
		}

		//return array_rand($arp);
	}
	
	function checkAnswer($qid, $ans, $con){
		$sql5 = "SELECT 
					`tbl_questions`.`idtbl_questions`,
					`tbl_questions`.`tbl_q`,
					`tbl_questions`.`tbl_ans1`,
					`tbl_questions`.`tbl_ans2`,
					`tbl_questions`.`tbl_rightAnswer`
				FROM `rotaract_igenius`.`tbl_questions`
				WHERE
					`tbl_questions`.`tbl_rightAnswer` = '".$ans."' AND
					`tbl_questions`.`idtbl_questions` = '".$qid."';";
		$result5 = mysqli_query($con, $sql5);
		
		if(mysqli_num_rows($result5) > 0){				
			while($row5 = mysqli_fetch_array($result5)){
				return 1;
			}			
		}else{
			return 0;
		}
	}
	
	// Receive something
	if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe' && $_REQUEST['hub_verify_token'] == $verify_token) {

		// Webhook setup request
		echo $_REQUEST['hub_challenge'];
	} else {

		// Other event

		$data = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
		if (!empty($data['entry'][0]['messaging'])) {
			foreach ($data['entry'][0]['messaging'] as $message) {
				$idtbl_users = userRegisteration($con, $message["sender"]["id"]);
				
				error_log(print_r($message, true));
				// Skipping delivery messages
				if (!empty($message['delivery'])) {
					continue;
				}

				$command = "";

				// When bot receive message from user
				if (!empty($message['message'])) {
					if(isset($message['message']['text'])){
						$command = $message['message']['text'];	
					}else if(isset($message['message']['attachments'])){
						foreach ($message['message']['attachments'] as $attachment) {
							if($attachment["type"] == "image"){
								foreach ($attachment["payload"] as $image) {
									// Open the file to get existing content
									$finfo = new finfo(FILEINFO_MIME_TYPE);
									$type = $finfo->buffer(file_get_contents($image));
									$current = file_get_contents($image);
									$filename = "";
									if($type == "image/jpeg"){
										$filename = "i".$message['sender']['id'].".jpg";
									}else if($type == "image/png"){
										$filename = "i".$message['sender']['id'].".png";
									}else if($type == "image/gif"){
										$filename = "i".$message['sender']['id'].".gif";
									}
									// Write the contents back to the file
									file_put_contents($filename, $current);
									// *** 1) Initialize / load image
									$resizeObj = new resize($filename);
									 
									// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
									$resizeObj -> resizeImage(1000, 1000, 'portrait');
									 
									// *** 3) Save image
									$filename = "1".$filename;
									$resizeObj -> saveImage($filename, 100);
									
									$png = imagecreatefrompng('./overlay.png');
									$jpeg = imagecreatefromjpeg($filename);

									list($width, $height) = getimagesize($filename);
									list($newwidth, $newheight) = getimagesize('./overlay.png');
									$out = imagecreatetruecolor($newwidth, $newheight);
									imagecopyresampled($out, $jpeg, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
									imagecopyresampled($out, $png, 0, 0, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
									imagejpeg($out, 'out.jpg', 100);
									$bot->send(new ImageMessage($message['sender']['id'], "https://www.rotaractcolombomidtown.org/igenius/bot/out.jpg"));
								}
							}
						}
					}else{
						
					}
				// When bot receive button click from user
				} else if (!empty($message['postback'])) {
					$pay = json_decode($message['postback']['payload'], true);
					$sql3 = "INSERT INTO `rotaract_igenius`.`tbl_users_has_questions`
									(
										`idtbl_questions`,
										`idtbl_users`,
										`givenAnswer`
									)VALUES(
										'".$pay["idtbl_questions"]."',
										'".$idtbl_users."',
										'".$pay["given"]."'
									);";
					mysqli_query($con, $sql3);
					/*$sql4 = "UPDATE
								`rotaract_igenius`.`tbl_users_has_questions`
							SET
								`givenAnswer` = '".$pay["given"]."'
							WHERE 
								`idtbl_questions` = '".$pay["idtbl_questions"]."' AND 
								`idtbl_users` = '".$idtbl_users."';";
					mysqli_query($con, $sql4);*/
					
					if(checkAnswer($pay["idtbl_questions"], $pay["given"], $con) == 0){
						$command = "shall we stop?";
						$bot->send(new Message($message['sender']['id'], "Ouuuh, I am afraid that answer is wrong"));
					}else{
						$command = "shall we continue?";
						$bot->send(new Message($message['sender']['id'], "Correct!, you can win a prize if you keep playing"));
					}
				}
				
				$command = strtolower($command);
				
				// Handle command
				switch ($command) {
					case "shall we continue?":
					case "shall we stop?":
					case "shall we play a game?":
						//
						if($command == "shall we stop?"){
							$bot->send(new Message($message['sender']['id'], "Oh no! you lost, it's ok you can start again if you want :)"));							
						}else{
							if($command == "shall we continue?"){
								$bot->send(new Message($message['sender']['id'], "Ah good job! next question, ready?"));
							}else{
								$bot->send(new Message($message['sender']['id'], "Ok lovely, Let's get started"));
							}
							$aA = alreadyAsked($message['sender']['id'], $con);
							if($aA != 0){
								$ac = askRQuestion($message['sender']['id'], $con);
								
								$ans1 = askQuestion($ac, $con);
								$ans1["given"] = $ans1["tbl_ans1"];
								
								$ans2 = askQuestion($ac, $con);
								$ans2["given"] = $ans2["tbl_ans2"];
								
								$ans3 = askQuestion($ac, $con);
								$ans3["given"] = $ans3["tbl_rightAnswer"];
								
								$bot->send(new StructuredMessage($message['sender']['id'],
								  StructuredMessage::TYPE_BUTTON,
								  [
									  'text' => askQuestion($ac, $con)["tbl_q"],
									  'buttons' => [
										  new MessageButton(MessageButton::TYPE_POSTBACK, $ans1["tbl_ans1"], json_encode($ans1)),
										  new MessageButton(MessageButton::TYPE_POSTBACK, $ans2["tbl_ans2"], json_encode($ans2)),
										  new MessageButton(MessageButton::TYPE_POSTBACK, $ans3["tbl_rightAnswer"], json_encode($ans3))
									  ]
								  ]
								));
								
							}else{
								$bot->send(new Message($message['sender']['id'], "Wow you answered all of our quiz questions, you must be smart :D"));
							}
						}
					break;

					// Other message received
					default:
						//$bot->send(new Message($message['sender']['id'], 'Sorry. I don’t understand you.'));
				}
			}
		}
	}
