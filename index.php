<?php
//Einlesen der JSON Datei
function sanitizeInputData($data){
    $data_new = [];
    foreach($data as $idx => $subarray){
        $sanIdx = sanitizeString($idx);
        if(is_array($subarray)){
            foreach($subarray as $key => $value){
                $data_new[$sanIdx][sanitizeString($key)] = sanitizeString($value);
            }
        }else{
            $data_new[$sanIdx] = sanitizeString($subarray);
        }
    }
    return $data_new;
    function sanitizeString($name) {
        if(is_array($name)) return false;
        $ret =  preg_replace(Array("#�#","#�#","#�#","#�#","#�#","#�#","#�#", "#[^A-Za-z0-9\+\?/\-:\(\)\.,' ]#"), Array("ae","oe","ue","Ae","Oe","Ue","sz","."), $name);
        return stripcslashes($ret);
    }
}


$DEBUG = true;


//l�sche generiertes pdf
if(isset($_GET['del'])&& $_GET['del']){
    if(!$DEBUG)
        unlink('zahlungsanweisung.pdf');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$domain = $data['meta']['domain'];
$data = sanitizeInputData($data);
if($DEBUG)
    var_dump($data);
//TODO wieder einpflegen
/*$type = sanitizeString($data['meta']['type']);
$id = sanitizeString($data['data']['id']);
$beleg_id = sanitizeString($data['meta']['belegid']);*/


// Baue den String f�r den Latex (f�r dort verwendete for schleife)
foreach($data['pdfs'] as $key => $value)
{
    $PicString = $PicString.($key+1)."/".$value.",";
}
$PicString = substr($PicString, 0, -1);

$data['data']['picpaths'] = $PicString;

//Downloade ben�tigte pdfs
foreach($data['pdfs'] as $bild)
{
    $download[] = $bild;
    //echo end(array_values($download));
    $hole = 'wget "'.$domain.'/FinanzAntragUI/external.php?fname='.$bild.'&id='.$beleg_id.'" -O '.$bild;
    if($DEBUG)
        var_dump($hole);
    shell_exec($hole);
}

//baue komavar String
$komavarString = "";
foreach($data["komavar"] as $name => $content){
    $komavarString .= "\setkomavar{".$name."}{".$content."}";
}
if($DEBUG){
    var_dump($komavarString);
}
file_put_contents("parameter.tex", $komavarString);

switch ($type) {
    case "auslagenerstattung":
        $name = "zahlungsanweisung";
        $data['data']['footerstring'] = "Auslagenerstattung Nr. {$id}";
        break;
    case "rechnung-zuordnung":
        $name = "zahlungsanweisung";
        $data['data']['footerstring'] = "Rechnung Nr. {$id}";
        break;
    case "bewilligung":
        $name = "briefkopf";
        break;
}

$befehl = "";

foreach($data['data'] as $key => $value){
    $befehl = $befehl . "\\newcommand{\\".$key."}{"."$value"."}";
}

$shellcmd =  "pdflatex \"". $befehl . "\\input{".$name."}\"";
if($DEBUG)
    var_dump($befehl);
$ret =  shell_exec($shellcmd);
shell_exec($shellcmd);
if($DEBUG)
    var_dump($ret);
if(!$DEBUG){
    header("Content-type: application/pdf");
    header("Content-disposition: attachment; filename='Auslagenerstattung-".$data["ID"].".pdf'");
}
readfile($name.".pdf");

foreach($download as $d){
    if(!$DEBUG)
        unlink($d);
}

?>
