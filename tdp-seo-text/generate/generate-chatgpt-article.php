<?php

function generate_chatgpt_seo_article($prompt, $geolocation_title)
{
     //set option
     global $statistics;
     global $statistics_data_fields;
     global $article_prompt;
     $api_key = SEO_DESCRIPTIONS_CHATGPT_API_KEY;

     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     //capitalize the geolocation title
     $geolocation_title = ucwords($geolocation_title);

     $counter = 0;

     if ($geolocation_title) {
          //search the geolocations array to find the one that matches the geolocation title
          foreach ($geolocations as $geolocation) {
               if ($geolocation->post_title == $geolocation_title) {
                    $geolocation_id = $geolocation->ID;
                    break;
               }
          }
     }

     if ($geolocation_id) {
          trigger_error("geolocation not found", E_USER_WARNING);

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

          $num_of_seo_gd_places = count($seo_gd_place_list);

          //replace the ids in seo_gd_place_list with the actual names
          foreach ($seo_gd_place_list as $key => $value) {
               $seo_gd_place_list[$key] = get_the_title($value);
          }

          //stringify the array
          $seo_gd_place_list = implode(", ", $seo_gd_place_list);

          //replace the ids with the actual names
          $seo_num_of_units_available = get_post_meta($geolocation_id, 'seo_num_of_units_available', true);

          // The prompt you want to send to ChatGPT
          $statitics_prompt_part = replace_variable_placeholders_chatgpt_description($statistics, $statistics_data_fields, $geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available, $archive_title_trimmed);

          trigger_error("statistics prompt: " . $statitics_prompt_part, E_USER_NOTICE);
          //structure article outline
     } else {
          trigger_error("geolocation not found", E_USER_WARNING);
     }

     $outlineMessage = [
          ["role" => "system", "content" => "Du er en skribent, der er ekspert i depotrum og opbevaringsrum. Opfør dig som en skribent, der er meget dygtig til SEO-skrivning og taler flydende dansk. Skriv altid 100% unik, SEO-optimeret, menneskeskrevne artikler på dansk som dækker det emne, der er angivet i prompten. Nummerér ikke overskrifterne. Skriv alle ord i titlen og overskrifterne med små bogstaver, undtagen det første ord. Skriv artiklen med dine egne ord i stedet for at kopiere og indsætte fra andre kilder. Overvej kompleksitet og varians, når du skaber indhold, idet du sikrer høje niveauer af begge dele uden at miste specificitet eller kontekst.  Brug fuldt detaljerede afsnit, der engagerer læseren. Skriv i en samtalestil, som om det var skrevet af et menneske (brug en uformel tone, brug personlige pronominer, hold det simpelt, engager læseren, brug aktiv stemme, hold det kort, brug retoriske spørgsmål og inkorporer analogier og metaforer)."],
          ["role" => "user", "content" =>  "Formulér outlinet/indholdsfortegnelsen/overskrifterne til artiklen: " . $prompt . ". Formulér mellem 4 og 6  relevanteoverskrifter."],
     ];

     // The data array
     $data = [
          'model' => 'gpt-4', // specifying the model
          'messages' => $outlineMessage, // your prompt
          'max_tokens' => 1500, // increase as needed
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

     $outlineResponse = $responseData['choices'][0]['message']['content'];

     if (strlen($response) < 50) {
          trigger_error("generated article putline  was under 50 chars, stopped the script", E_USER_WARNING);
          exit();
     }

     trigger_error("article outline: " . $prompt .  " " . $outlineResponse, E_USER_NOTICE);


     if ($geolocation_id) {
          $secondprompt = "Skriv artiklen: " . $prompt . " med overskrifterne: " . $outlineResponse . ". Benyt eventuelt statistikken om opbevaring i lokationen " . $geolocation_title . " fra tjekdepot.dk til at skrive artiklen: " . $statitics_prompt_part . ". Lagerhoteller i lokationen: " . $seo_gd_place_list . " Andre instruktioner: " . $article_prompt;
     } else {
          $secondprompt = "Skriv artiklen: " . $prompt . " med overskrifterne: " . $outlineResponse . ". Andre instruktioner: " . $article_prompt;
     }

     $mainArticleMessage = [
          ["role" => "system", "content" => "Du er en skribent, der er ekspert i depotrum og opbevaringsrum. Opfør dig som en skribent, der er meget dygtig til SEO-skrivning og taler flydende dansk. Skriv altid 100% unik, SEO-optimeret, menneskeskrevne artikler på dansk som dækker det emne, der er angivet i prompten. Nummerér ikke overskrifterne. Skriv alle ord i titlen og overskrifterne med små bogstaver, undtagen det første ord. Skriv artiklen med dine egne ord i stedet for at kopiere og indsætte fra andre kilder. Overvej kompleksitet og varians, når du skaber indhold, idet du sikrer høje niveauer af begge dele uden at miste specificitet eller kontekst.  Brug fuldt detaljerede afsnit, der engagerer læseren. Skriv i en samtalestil, som om det var skrevet af et menneske (brug en uformel tone, brug personlige pronominer, hold det simpelt, engager læseren, brug aktiv stemme, hold det kort, brug retoriske spørgsmål og inkorporer analogier og metaforer)."],
          ["role" => "user", "content" =>  $secondprompt],
     ];


     trigger_error("article prompt: " . $secondprompt, E_USER_NOTICE);

     // The data array
     $data = [
          'model' => 'gpt-4', // specifying the model
          'messages' => $mainArticleMessage, // your prompt
          'max_tokens' => 4000, // increase as needed
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

     $article = $responseData['choices'][0]['message']['content'];

     if (strlen($article) < 150) {
          trigger_error("generated article result was under 150 chars, stopped the script", E_USER_WARNING);
          exit();
     }

     //make the article title the prompt but remove "skriv artiklen: " in case it's there
     $title = str_replace("skriv artiklen: ", "", $prompt);

     // Create a new post with the article contents and title. Use a html block for the content
     $post = array(
          'post_title'   => $title,
          'post_content' => $article,
          'post_status'  => 'draft',
          'post_author'  => 10, //majken holm
          'post_type'    => 'post',
          'post_category' => array(139) // Use the ID of the category 'viden-raad'
     );

     // Insert the post into the database
     $post_id = wp_insert_post($post);

     // Set tags for the post
     wp_set_post_tags($post_id, 'chatgpt article', true);

     trigger_error("finished article: " . $prompt .  " " . $article, E_USER_NOTICE);

     trigger_error("generated chatgpt article", E_USER_NOTICE);
}

$statistics = '
statistik om opbevaring i [location] fra tjekdepot.dk
[seo_num_of_units_available]
[num_of_seo_gd_places]
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
[very large size average m3 price]';

$article_prompt = '

brug ordene “depotrum, opbevaringsrum, opbevaring eller lagerrum” i stedet for ordenen “enhed eller opbevaringsenhed”. brug “m²” i stedet for kvadratmeter. brug “m³” i stedet for kubikmeter.

undlad at bruge “:” i overskrifterne. sæt h2-tags om overskrifterne. et afsnit er hele teksten under en overskrift. sæt p-tags om hvert afsnit, men brug kun et p-tag for hver overskrift. undlad at bruge stort begyndelsesbogstav i hvert ord i overskrifterne.

Prioriter substans og undgå fuffy, fyld-indhold. brug en uhøjtidelig tone uden fyldeord og superlativer. skriv koncist og uden for mange floskler. brug en naturlig professionel, informativ skrivestil og tone. brug ikke pompøse ord. brug kun danske ord. Skriv med selvsikkerhed, brug et klart og præcist sprog, vis ekspertise, og vær gennemsigtig. brug danske formuleringer og sætningskonstruktioner.

læg vægt på, at man kan finde depotrum i lokationen på tjekdepot.dk. 
artiklen skal være på fire til fem afsnit, og længden skal være omkring 1300 ord. hvert afsnit skal være på minimum 300 ord. giv hvert afsnit en overskrift, der indeholder mindst et af nøgleordene. skriv hele artiklen, lad være med kun at skrive en skitse.
';
