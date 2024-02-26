<?php

function generate_missing_chatgpt_geolocation_descriptions($num)
{
     //set option
     global $description_prompt;
     $api_key = get_option('seo_decriptions_api_key');
     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     $counter = 0;

     foreach ($geolocations as $geolocation) {
          if ($counter >= $num) {
               break;
          }

          $geolocation_id = $geolocation->ID;

          $description = get_post_meta($geolocation_id, 'description', true);

          if ($description) {
               continue;
          }

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

          $num_of_seo_gd_places = count($seo_gd_place_list);

          // The prompt you want to send to ChatGPT
          $iterationPrompt = str_replace("[location]", $archive_title_trimmed, $description_prompt);

          $messages = [
               ["role" => "user", "content" =>  $iterationPrompt],
          ];

          // The data array
          $data = [
               'model' => 'gpt-4', // specifying the model
               'messages' => $messages, // your prompt

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

$description_prompt = "skriv en kort tekst/artikel om området. læg vægt på fakta om området som områdets placering i landet, områdets omdømme, nøgletal om indbyggere og erhverv, områdets udvikling.lområdets forbindelserne til nærliggende byer eller bydele. find gerne selv på flere emner. undlad emner, der ikke er tilstrækkelig information om.  Prioriter substans og undgå fuffy, fyld-indhold. 

brug en uhøjtidelig tone uden fyldeord og superlativer. skriv koncist og uden for mange floskler. brug en naturlig professionel, informativ skrivestil og tone. brug ikke pompøse ord. brug kun danske ord. Skriv med selvsikkerhed, brug et klart og præcist sprog, vis ekspertise, og vær gennemsigtig

teksten skal være et sammenhængene afsnit på omkring 300 ord.

område: [location]";
