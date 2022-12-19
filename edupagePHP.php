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

$css ="*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-size:30px;
}
body{
    display: flex;
    justify-content: center;
    overflow: hidden;
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
}

.table-container {
  overflow: hidden;
  position: absolute;
  top: 100%;
  
  animation: scroll-up 25s linear infinite;
  
}


@keyframes scroll-up {
  from { top: 100%; }
  to { top: -100%; }
}";

$objavi="<head><meta charset='UTF-8'><style>".$css."</style></head>";

$objavi=$objavi."<div class='table-container'>";
$objavi=$objavi."<table>";

$objavi=$objavi."<tr><td style='font-weight:bold'>RAZRED</td><td style='font-weight:bold'>SAT</td><td style='font-weight:bold'>PREDMET &nbsp;&nbsp;&nbsp; DANAS</td></tr>";
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
            if($foreach_stage ==2)$objavi=$objavi.$trenutni_razred;     //ako se razred nije ispisao ispisi

            $objavi=$objavi."<td>".$item->nodeValue.".sat </td>";     //razred
            $foreach_stage =1;
    }
        if (strpos($item->parentNode->getAttribute('class'), 'info')!==false){
            $objavi=$objavi."<td>".$item->nodeValue."</td></tr>";         //tko-koga + promjena prostorije        TODO promjena prostorije!
            $foreach_stage =2;
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
$objavi=$objavi."<tr><td style='font-weight:bold'>RAZRED</td><td style='font-weight:bold'>SAT</td><td style='font-weight:bold'>PREDMET &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; SUTRA</td></tr>";

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
            if($foreach_stage ==2)$objavi=$objavi.$trenutni_razred;     //ako se razred nije ispisao ispisi

            $objavi=$objavi."<td>".$item->nodeValue.".sat </td>";     //razred
            $foreach_stage =1;
    }
        if (strpos($item->parentNode->getAttribute('class'), 'info')!==false){
            $objavi=$objavi."<td>".$item->nodeValue."</td></tr>";         //tko-koga + promjena prostorije        TODO promjena prostorije!
            $foreach_stage =2;
        }
    }
    else
        $preskacem--;
}


$objavi=$objavi."<tr colspan='3'><td>kraljić,petković,gotal,mj</td></tr>";
$objavi=$objavi."<style>*{color:black;}</style>";

$objavi=$objavi."</table>";
$objavi=$objavi."</div>";
echo $objavi;

$myfile = fopen("zamjeneDanas.htm", "w");
fwrite($myfile, $objavi);

//echo $response;

?>
