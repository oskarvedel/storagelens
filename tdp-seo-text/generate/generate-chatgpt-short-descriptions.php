<?php

function generate_missing_chatgpt_geolocation_short_descriptions($num)
{
     global $short_description_prompt;
     $api_key = get_option('seo_decriptions_api_key');
     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     $counter = 0;

     foreach ($geolocations as $geolocation) {
          if ($counter >= $num) {
               break;
          }

          $geolocation_id = $geolocation->ID;

          $description = get_post_meta($geolocation_id, 'description', true);

          if (!$description) {
               continue;
          }

          $short_description = get_post_meta($geolocation_id, 'short_description', true);

          if ($short_description) {
               continue;
          }

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

          $num_of_seo_gd_places = count($seo_gd_place_list);

          // The prompt you want to send to ChatGPT
          $iterationPrompt = str_replace("[location]", $archive_title_trimmed, $short_description_prompt);

          $iterationPrompt = str_replace("[description]", $description, $iterationPrompt);

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

          if (strlen($message) < 10) {
               trigger_error("generated chatgpt short_description was under 10 chars, stopped the script", E_USER_WARNING);
               break;
          }

          if (strlen($message) > 200) {
               trigger_error("generated chatgpt short_description was over 200 chars, stopped the script", E_USER_WARNING);
               break;
          }

          if (strlen($message) > 50) {
               update_post_meta($geolocation_id, 'short_description', $message);
          }

          trigger_error("generated chatgpt short_description for $archive_title_trimmed: $message", E_USER_NOTICE);

          $counter++;
     }
     trigger_error("generated chatgpt short_descriptions for $counter geolocations", E_USER_NOTICE);
}

$short_description_prompt = "skriv en meget kort beskrivelse af lokationen. teksten skal være på 150 tegn. tag udgangspunkt i originalteksten. 

område: [location] 

originaltekst: [description]";
