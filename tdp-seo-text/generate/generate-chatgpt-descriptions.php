<?php

function generate_missing_chatgpt_geolocation_descriptions($num)
{
     // xdebug_break();
     //set option
     global $description_prompt;
     global $statistics_data_fields;
     $api_key = SEO_DESCRIPTIONS_CHATGPT_API_KEY;

     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     $counter = 0;

     foreach ($geolocations as $geolocation) {
          if ($counter >= $num) {
               break;
          }

          $geolocation_id = $geolocation->ID;

          $description = get_post_meta($geolocation_id, 'description', true);

          if ($description) {
               // trigger_error("description already exists for $geolocation_id", E_USER_NOTICE);
               continue;
          }

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

          $num_of_seo_gd_places = count($seo_gd_place_list);

          // The prompt you want to send to ChatGPT
          $iterationPrompt = str_replace("[location]", $archive_title_trimmed, $description_prompt);

          $iterationPrompt = get_statistics_data_fields_values($iterationPrompt, $statistics_data_fields, $geolocation_id);

          // trigger_error("generating chatgpt desc for $archive_title_trimmed with prompt: $iterationPrompt", E_USER_NOTICE);

          $messages = [
               ["role" => "user", "content" =>  $iterationPrompt],
          ];

          // The data array
          $data = [
               'model' => 'gpt-4', // specifying the model
               'messages' => $messages, // your prompt
               'max_tokens' => 6000, // increase as needed
          ];

          // Initialize cURL session
          $ch = curl_init();

          // Set cURL options
          curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions'); // API URL
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
               'Content-Type: application/json',
               "Authorization: Bearer $api_key"
          ]);

          $response = "";
          // Execute cURL session and get the response
          $response = curl_exec($ch);

          if (curl_errno($ch)) {
               $curlErrorMessage = curl_error($ch);
               trigger_error('cURL Error: ' . $curlErrorMessage, E_USER_WARNING);
               break;
          }

          // Close cURL session
          curl_close($ch);

          // Decode the response
          $responseData = json_decode($response, true);

          $message = $responseData['choices'][0]['message']['content'];

          if (strlen($message) < 150) {
               trigger_error("generated chatgpt description was under 150 chars, stopped the script", E_USER_WARNING);
               break;
          }

          if (strlen($message) > 50) {
               update_post_meta($geolocation_id, 'description', $message);
          }

          trigger_error("generated chatgpt description for $archive_title_trimmed: $message", E_USER_NOTICE);

          $counter++;
     }
     trigger_error("generated chatgpt descriptions for $counter geolocations", E_USER_NOTICE);
}

$description_prompt = '

statistik om opbevaring i [location] fra tjekdepot.dk
[num of units available]
[num of m2 available]
[num of m3 available]
[average price]
[lowest price]
[highest price]
[smallest m2 size]
[largest m2 size]
[average m2 price]
[average m3 price]
[mini size lowest price]
[mini size highest price]
[mini size average price]
[mini size average m2 price]
[mini size average m3 price]
[small size lowest price]
[small size highest price]
[small size average price]
[small size average m2 price]
[small size average m3 price]
[medium size lowest price]
[medium size highest price]
[medium size average price]
[medium size average m2 price]
[medium size average m3 price]
[large size lowest price]
[large size highest price]
[large size average price]
[large size average m2 price]
[large size average m3 price]
[very large size lowest price]
[very large size highest price]
[very large size average price]
[very large size average m2 price]
[very large size average m3 price]


alle priser er i kroner.

skriv en artikel opbevaring i [location]. artiklen skal handle om mulighederne for opbevaring i [location] samt om at flytte til århus.

nøgleord:
	•	opbevaring i [location]
	•	opbevaringsrum i [location]
	•	depotrum i [location]
	•	opbevaring af møbler i [location]
	•	opmagasinering i [location]
	•	opmagasinering af møbler i [location]
	•	lagerhotel i [location]
	•	self storage i [location]

emner:
	•	på priserne for depotrum i området (ud fra statistik)
	•	fakta om området som [location] placering i landet
	•	[location] omdømme som tilflytter
	•	nøgletal om indbyggere og erhverv
	•	hvilke størrelser depotrum der er tilgængelige i [location] (ud fra statistik)
	•	billig opbevaring i [location] (ud fra statistik)
	•	opbevaring af møbler hvis man skal flytte til [location] (ud fra statistik)
	•	nøgletal om [location] udvikling
	•	områdets forbindelserne til nærliggende byer eller bydele.

undlad emner, der ikke er tilstrækkelig information om. 


Prioriter substans og undgå fuffy, fyld-indhold. brug en uhøjtidelig tone uden fyldeord og superlativer. skriv koncist og uden for mange floskler. brug en naturlig professionel, informativ skrivestil og tone. brug ikke pompøse ord. brug kun danske ord. Skriv med selvsikkerhed, brug et klart og præcist sprog, vis ekspertise, og vær gennemsigtig. brug danske formuleringer og sætningskonstruktioner. husk at bruge hvert nøgleord flere gange.

brug nøgleordene i hele artiklen, og brug mindst et af nøgleordene i hver overskrift. brug hvert nøgleord flere gange igennem hele teksten. brug ikke det samme nøgleord i flere overskrifter.

læg vægt på, at man kan finde depotrum i [location] på tjekdepot.dk. 

brug ordet “depotrum” i stedet for “enhed eller opbevaringsenhed”. brug “m²” i stedet for kvadratmeter. brug “m³” i stedet for kubikmeter.

undlad at bruge “:” i overskrifterne. sæt h2-tags om overskrifterne. et afsnit er hele teksten under en overskrift. sæt p-tags om hvert afsnit, men brug kun et p-tag for hver overskrift. undlad at bruge stort begyndelsesbogstav i hvert ord i overskrifterne.

artiklen skal være på fire til fem afsnit, og længden skal være omkring 1300 ord. hvert afsnit skal være på minimum 300 ord. hcvertgiv hvert afsnit en overskrift, der indeholder mindst et af nøgleordene. skriv hele artiklen, lad være med kun at skrive en skitse.
';

function get_statistics_data_fields_values($input_text, $statistics_data_fields, $geolocation_id)
{
     foreach ($statistics_data_fields as $field) {
          $value = get_post_meta($geolocation_id, $field, true);
          if (!empty($value)) {
               $rounded = floatval(round($value, 2));
               $numberformat = number_format($value, 0, ',', '.');
               $input_text = str_replace("[$field]", "[$field]: " . $numberformat, $input_text);
          } else {
               $input_text = str_replace("[$field]", "Ukendt", $input_text);
          }
     }
     return $input_text;
}
