<?php
//echo phpinfo()."<br>";
$subdomain = "tsck";
$username = "ovo@nije.potrebno";
$password = "niOvo";

$url = 'https://'.$subdomain.'.edupage.org/login/index.php';
libxml_use_internal_errors(true);
$ch = curl_init('https://'.$subdomain.'.edupage.org/login/index.php');
$cookiefile;
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt ($ch, CURLOPT_COOKIEJAR, 'PHPSESSID'); 
$response = curl_exec($ch);
curl_close($ch);

$dom = new DOMDocument();
$dom->loadHTML($response);
$xpath = new DOMXPath($dom);
$span = $xpath->query('//*[@id="login_Login_1"]/form/input/@value');
$csrf = $span[0]->value;
libxml_use_internal_errors(false);

$data = [
	"username" => $username, 
	"password" => $password,                                         
	"csrfauth" => $csrf,
];

$ch = curl_init('https://'.$subdomain.'.edupage.org/login/edubarLogin.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt ($ch, CURLOPT_COOKIEFILE, 'PHPSESSID');
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);
$session_cookie="";
foreach(preg_split("/\s/", $info["request_header"]) as $line)
	if(preg_match("/PHPSESSID/i", $line))	
	$session_cookie =substr($line,10,32 );

if(strpos($response,"skgdLoginBadMsg")){
	;//echo "Wrong username or password";
}

$gsechash = "";
foreach(preg_split("/((\r?\n)|(\r\n?))/", $response) as $line)
    if(strpos($line, "gsechash"))
		$gsechash =substr($line,14,8 );
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*danasnja zamjena*/
$data = [
	"__args" =>
	[
		null,
		[
			"date" => date("Y-m-d", strtotime('now+0day')),             //dio za odredivanje koji dan dohvacamo
			"mode" => "classes"
		]
	],
	"__gsh" => $gsechash
];

$payload =json_encode($data);
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://'.$subdomain.'.edupage.org/substitution/server/viewer.js?__func=getSubstViewerDayDataHtml',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>$payload,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Cookie: PHPSESSID='.$session_cookie
  ),
));

$response = curl_exec($curl);

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom -> loadHTML($response);

$items = $dom -> getElementsByTagName('span');

$objavi="#######<br>Današnje zamjene<br><b>razred</b><br>sat<br>";
/*preskoci jedan segment?*/
$preskacem=1;
foreach ($items as $item){
    if (!$preskacem){
        //echo $item->nodeId." ".$item->nodeValue." ".$item->parentNode->getAttribute('class')."<br>-----------<br>";
        if (strpos($item->parentNode->getAttribute('class'), 'header')!==false)
            $objavi=$objavi."----------<br><b>".$item->nodeValue."</b><br>";     //razred
        if (strpos($item->parentNode->getAttribute('class'), 'period')!==false)
            $objavi=$objavi."".$item->nodeValue."<br>";                 //sat
        if (strpos($item->parentNode->getAttribute('class'), 'info')!==false)
            ;                                                           //tko-koga + promjena prostorije        TODO promjena prostorije!
    }
    else
        $preskacem--;
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*zamjene sljedeći dan*/
$data = [
	"__args" =>
	[
		null,
		[
			"date" => date("Y-m-d", strtotime('now-1day')),             //dio za odredivanje koji dan dohvacamo
			"mode" => "classes"
		]
	],
	"__gsh" => $gsechash
];

$payload =json_encode($data);

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://'.$subdomain.'.edupage.org/substitution/server/viewer.js?__func=getSubstViewerDayDataHtml',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>$payload,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Cookie: PHPSESSID='.$session_cookie
  ),
));

$response = curl_exec($curl);

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom -> loadHTML($response);

$items = $dom -> getElementsByTagName('span');

$objavi=$objavi."########<br>Sutrašnje zamjene<br><b>razred</b><br>sat<br>";
/*preskoci jedan segment?*/
$preskacem=1;
foreach ($items as $item){
    if (!$preskacem){
        //echo $item->nodeId." ".$item->nodeValue." ".$item->parentNode->getAttribute('class')."<br>-----------<br>";
        if (strpos($item->parentNode->getAttribute('class'), 'header')!==false)
            $objavi=$objavi."----------<br><b>".$item->nodeValue."</b><br>";     //razred
        if (strpos($item->parentNode->getAttribute('class'), 'period')!==false)
            $objavi=$objavi."".$item->nodeValue."<br>";                 //sat
        if (strpos($item->parentNode->getAttribute('class'), 'info')!==false)
            ;//$objavi=$objavi.$item->nodeValue."<br>";                                                           //tko-koga + promjena prostorije        TODO promjena prostorije!
    }
    else
        $preskacem--;
}
$objavi=$objavi."oooooooooooo<br>kraljić<br>gotal<br>mj<br><b>https://tsck.edupage.org/substitution/</b><br>oooooooooooo";
echo $objavi;

/*curl_close($curl);
$myfile = fopen("testfile.txt", "w");
fwrite($myfile, $response);
*/
//echo $response;

?>
