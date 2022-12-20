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
$dom -> loadHTML('<?xml encoding="utf-8" ?>' . $response);

$items = $dom -> getElementsByTagName('span');

$css ="
:root{
	--container-height:-1000px;
}
*{
	margin: 0;
	padding: 0;
	box-sizing: border-box;
	font-size:20px;
	}
	body{
	display: flex;
	justify-content: center;
	overflow: hidden;
	background-color: black;
	margin:0;
	color: white;
	}
	table {
	border-collapse: collapse;
	margin: 5vh 0;
	}
	tr {
	border: 1px solid black;
	}
	td {
	padding: 10px;
	}
	tr:nth-child(odd) {
	background-color: #abd18e;
	color: black;
	}
	tr:nth-child(even) {
	color: white;
	}
	.table-container {
	overflow: hidden;
	position: fixed;
	top: 100%;
	animation: scroll-up 60s linear infinite;
	}
	@keyframes scroll-up {
	from { top: 100%; }
	to { top: var(--container-height); }
	}";
$objavi="<html>";
$objavi="<head><meta charset='UTF-8'><style>".$css."</style></head>";

$objavi=$objavi."<body>";
$objavi=$objavi."<div class='table-container'>";
$objavi=$objavi."<table>";

$objavi=$objavi."<tr><td colspan='3' style='text-align: center;font-size:40px;font-weight:bold;'>DANAŠNJE ZAMJENE</td></tr>
				<tr><td style='font-weight:bold'>RAZRED</td><td style='font-weight:bold'>SAT</td><td style='font-weight:bold'>PREDMET</td></tr>";
/*preskoci jedan segment?*/
$preskacem=1;
$trenutni_razred= 1;
$foreach_stage=0;//korišteno kako bi se znalo ima li neki razred više sati zamjene jer zada ne upisuje razred automatski
foreach ($items as $item){
    if (!$preskacem){
        //echo $item->nodeId." ".$item->nodeValue." ".$item->parentNode->getAttribute('class')."<br>-----------<br>";
        if (strpos($item->parentNode->getAttribute('class'), 'header')!==false){
            if($item->nodeValue > 0){
                $objavi=$objavi."<tr><td style='font-weight:bold'>".$item->nodeValue."</td>";     //razred
                $trenutni_razred = "<tr><td style='font-weight:bold'>".$item->nodeValue."</td>";
                $foreach_stage = 0;
            }    
        }    
        if (strpos($item->parentNode->getAttribute('class'), 'period')!==false){
            if($foreach_stage == 2)$objavi=$objavi.$trenutni_razred;     //ako se razred nije ispisao ispisi

            $objavi=$objavi."<td>".$item->nodeValue.".sat </td>";     //razred
            $foreach_stage = 1;
        }
        if (strpos($item->parentNode->getAttribute('class'), 'info')!==false){
            if(strpos($item->nodeValue, "➔")!==false) {
                if(strpos($item->nodeValue, "Zamjena")!==false)			//PHP verzija < 8
                    $item->nodeValue = substr($item->nodeValue, 0, strpos($item->nodeValue," - Zamjena:"));
                $objavi=$objavi."<td>".$item->nodeValue."</td></tr>";         //tko-koga + promjena prostorije        TODO promjena prostorije! 
            }
            else {
                $objavi=$objavi."<td> Slobodni sat </td>";
            }
            
            $foreach_stage = 2;
        }
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
			"date" => date("Y-m-d", strtotime('now+1day')),             //dio za odredivanje koji dan dohvacamo
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
$dom -> loadHTML('<?xml encoding="utf-8" ?>' . $response);

$items = $dom -> getElementsByTagName('span');
$objavi=$objavi."<tr><td colspan='3' style='text-align: center;font-size:40px;font-weight:bold;'>SUTRAŠNJE ZAMJENE</td></tr>
				<tr><td style='font-weight:bold'>RAZRED</td><td style='font-weight:bold'>SAT</td><td style='font-weight:bold'>PREDMET</td></tr>";

/*preskoci jedan segment?*/
$preskacem=1;
$trenutni_razred= 1;
$foreach_stage=0;//korišteno kako bi se znalo ima li neki razred više sati zamjene jer zada ne upisuje razred automatski
foreach ($items as $item){
    if (!$preskacem){
        //echo $item->nodeId." ".$item->nodeValue." ".$item->parentNode->getAttribute('class')."<br>-----------<br>";
        if (strpos($item->parentNode->getAttribute('class'), 'header')!==false){
            if($item->nodeValue > 0){
                $objavi=$objavi."<tr><td style='font-weight:bold'>".$item->nodeValue."</td>";     //razred
                $trenutni_razred = "<tr><td style='font-weight:bold'>".$item->nodeValue."</td>";
                $foreach_stage =0;
            }    
        }

            
        if (strpos($item->parentNode->getAttribute('class'), 'period')!==false){
            if($foreach_stage == 2)$objavi=$objavi.$trenutni_razred;     //ako se razred nije ispisao ispisi

            $objavi=$objavi."<td>".$item->nodeValue.".sat </td>";     //razred
            $foreach_stage = 1;
    }
        if (strpos($item->parentNode->getAttribute('class'), 'info')!==false){
            if(strpos($item->nodeValue, "➔")!==false) {
                if(strpos($item->nodeValue, "Zamjena")!==false)			//PHP verzija < 8
                    $item->nodeValue = substr($item->nodeValue, 0, strpos($item->nodeValue," - Zamjena:"));
                $objavi=$objavi."<td>".$item->nodeValue."</td></tr>";         //tko-koga + promjena prostorije        TODO promjena prostorije! 
            }
            else {
                $objavi=$objavi."<td> Slobodni sat </td>";
            }
            $foreach_stage = 2;
        }
    }
    else
        $preskacem--;
}


$objavi=$objavi."<tr><td colspan='3'> <center>Kraljić, Petković, Gotal, MJ</center></td></tr>";
$objavi=$objavi."<style></style>";

$objavi=$objavi."</table>";
$objavi=$objavi."</div>";
$objavi=$objavi."<script>document.documentElement.style.setProperty('--container-height', -document.querySelector('.table-container').clientHeight+'px');</script>";
$objavi=$objavi."</body>";
$objavi=$objavi."</html>";
echo $objavi;

$myfile = fopen("zamjeneDanas.htm", "w");
fwrite($myfile, $objavi);

//echo $response;

?>
