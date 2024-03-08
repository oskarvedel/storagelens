<?php

function generate_chatgpt_seo_article($prompt)
{
     //set option
     global $article_prompt;
     global $statistics_data_fields;
     $api_key = SEO_DESCRIPTIONS_CHATGPT_API_KEY;

     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     $counter = 0;

     // foreach ($geolocations as $geolocation) {
     //      if ($counter >= $num) {
     //           break;
     //      }

     $geolocation_id = 100;

     // $description = get_post_meta($geolocation_id, 'description', true);

     // if ($description) {
     //      // trigger_error("description already exists for $geolocation_id", E_USER_NOTICE);
     //      continue;
     // }

     $archive_title_trimmed = get_the_title($geolocation_id);

     $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

     $seo_num_of_units_available = get_post_meta($geolocation_id, 'seo_num_of_units_available', true);

     $num_of_seo_gd_places = count($seo_gd_place_list);


     //generate article with chatgpt 3.5
     $messages = [
          ["role" => "user", "content" =>  $prompt . " sæt h3-tags om alle overskrifterne"],
     ];

     $messages[] = [
          "role" => "system",
          "content" => "Du er en skribent, der er ekspert i depotrum og opbevaringsrum. Opfør dig som en skribent, der er meget dygtig til SEO-skrivning og taler flydende dansk. Skriv altid 100% unik, SEO-optimeret, menneskeskrevne artikler på dansk med præcis 3 overskrifter og underoverskrifter, der dækker det emne, der er angivet i prompten. Markér overskrifterne med H3-tags. Nummerér ikke overskrifterne. Skriv alle ord i titlen og overskrifterne med små bogstaver, undtagen det første ord. Skriv artiklen med dine egne ord i stedet for at kopiere og indsætte fra andre kilder. Overvej kompleksitet og varians, når du skaber indhold, idet du sikrer høje niveauer af begge dele uden at miste specificitet eller kontekst. Artiklen skal være ca. 700 ord lang. Brug fuldt detaljerede afsnit, der engagerer læseren. Skriv i en samtalestil, som om det var skrevet af et menneske (brug en uformel tone, brug personlige pronominer, hold det simpelt, engager læseren, brug aktiv stemme, hold det kort, brug retoriske spørgsmål og inkorporer analogier og metaforer). Sæt h3-tags om alle overskrifterne i artiklen."
     ];

     // The data array
     $data = [
          'model' => 'ft:gpt-3.5-turbo-0125:personal::90W9CS0T', // specifying the model
          'messages' => $messages, // your prompt
          'max_tokens' => 3000, // increase as needed
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
          // break;
     }

     // Close cURL session
     curl_close($ch);

     // Decode the response
     $responseData = json_decode($response, true);

     $response = $responseData['choices'][0]['message']['content'];

     if (strlen($response) < 150) {
          trigger_error("generated chatgpt description was under 150 chars, stopped the script", E_USER_WARNING);
          exit();
     }

     if (strlen($response) > 50) {
          // update_post_meta($geolocation_id, 'description', $message);
     }

     //structure article with chatgpt 4
     $messages = [
          ["role" => "user", "content" =>  "Giv denne artikel en struktur med 3-4 passende underoverskrifter. Sæt h3-tags om underoverskrifterne. : artikel:" . $response],
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
          // break;
     }

     // Close cURL session
     curl_close($ch);

     // Decode the response
     $responseData = json_decode($response, true);

     $message = $responseData['choices'][0]['message']['content'];

     if (strlen($message) < 150) {
          trigger_error("generated chatgpt description was under 150 chars, stopped the script", E_USER_WARNING);
          exit();
     }

     if (strlen($message) > 50) {
          // update_post_meta($geolocation_id, 'description', $message);
     }

     //make the article title the prompt but remove "skriv artiklen: " in case it's there
     $title = str_replace("skriv artiklen: ", "", $prompt);

     // Create a new post with the article contents and title. Use a html block for the content
     $post = array(
          'post_title'   => $title,
          'post_content' => '<!-- wp:html -->' . $message . '<!-- /wp:html -->',
          'post_status'  => 'draft',
          'post_author'  => 1,
          'post_type'    => 'post',
          'post_category' => array(139) // Use the ID of the category 'viden-raad'
     );

     // Insert the post into the database
     $post_id = wp_insert_post($post);



     trigger_error("article: " . $prompt .  " " . $message, E_USER_NOTICE);

     // $counter++;
     // }
     trigger_error("generated chatgpt article", E_USER_NOTICE);
}
