<?php
  $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>";      
  //***********************************************************************************************************************
  // V1.0 : Script qui fournit l'etat des ouvertures parametrees et le message vocal associe
  // V1.1 : Version sans appel API à l'utilisation
  //*************************************** API eedomus ******************************************************************
  //*************************************** Messages personnels***********************************************************
  $msg_allclosed = "Apres vérification, tout est bien fermé"; //  permet de gérer les traductions dans d'autres langues
  $msg_open = "Je detecte que"." "; //  permet de gérer les traductions dans d'autres langues
  $tabouvertures = array();

  $mode = getArg("mode");
   
  if ($mode == 'list')
  {
    $url = "https://api.eedomus.com/get?api_user=API_USER&api_secret=API_SECRET&action=periph.list";
    $periph_list = sdk_json_decode(utf8_encode(httpQuery($url,'GET')));
    
    foreach($periph_list["body"] as $device)
    {
      if (
         $device['usage_id'] == 10 // Porte
      || $device['usage_id'] == 11 // Fenêtre
      || $device['usage_id'] == 12 // Portail
      )
      {
        $device_list .= $device['periph_id'].",";
        $devices_names .= $device['periph_id']." = ".utf8_decode($device['name'])."<br>";
      }
    }
    
    $device_list = trim($device_list, ",");
    
    echo "Votre liste de contacteurs :"."<br>"."<br>";
    echo "<input id=\"device_list\" type=\"text\" size=\"100\" name=\"device_list\" value=\"$device_list\" onclick=\"this.select();\" >";
    echo "<br>"."<br>";
    echo $devices_names;
    
    die();
  }
      
   // recuperation des ID depuis la requete
   $periphs = getArg("periphIds", $mandatory = true, $default = '');
   $resultPeriphId = getArg("resultPeriphId", $mandatory = false, $default = '');
   $tabPeriphs = explode(",", $periphs);

   //reset de l'indicateur 'portes ouverte'
   if ($resultPeriphId) 
   {
      setValue($resultPeriphId, 0);
   } 
   
   // recuperation du nom des peripheriques
   foreach($tabPeriphs as $periphId)
   {
     //$urlValue =  $urlBase.$periphId;
     $arrValue = getValue($periphId, /*$value_text*/ true);

     $tabouvertures[] = array("NAME" => $arrValue["full_name"], "API" => $periphId, "ETAT" => $arrValue["value"], "ETAT_TXT" => $arrValue["value_text"]);
   }

   //**********************************************************************************************************************
   $xml .= "<OUVERTURES>";
   $idoors = 1;
   $nbouvert = 0;
   $annonce = $msg_allclosed;
   foreach($tabouvertures as $ouvertures) {
      $arrValue = $ouvertures["ETAT"];
      if ($arrValue <> 0) {
         $ouvertures["ETAT"] = 1;
         $nbouvert++;
         if ($nbouvert == 1) {
            $annonce = $msg_open.$ouvertures["NAME"];
         }
         else {
            $annonce = $annonce." "."et"." ".$ouvertures["NAME"];
         }
      }
      $xml .= "<OUVERTURE_".$idoors."><TYPE>".$ouvertures["NAME"]."</TYPE>";
      $xml .= "<ETAT>".$ouvertures["ETAT_TXT"]."</ETAT></OUVERTURE_".$idoors.">\n";
      $idoors++;
   }
   if ($nbouvert == 1) {
      $annonce .= " "."est ouverte.";
   } else if ($nbouvert > 1) {
      $annonce .= " "."sont ouvertes.";
   }
   
   if (($nbouvert > 0) && $resultPeriphId) 
   {
      setValue($resultPeriphId, 100);
   }
       
   $xml .= "<MESSAGE>".$annonce."</MESSAGE>";
   $xml .= "</OUVERTURES>";
   sdk_header('text/xml');
   echo $xml;
?>