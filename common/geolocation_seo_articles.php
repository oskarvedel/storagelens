<?php

function import_article_titles()
{
  //get all geolocations
  $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

  //get the JSON data
  global $titles;

  //decode the JSON data into an associative array
  $titles_data = json_decode($titles, true);

  //match each geolocation wp title with the json city title
  $geolocations_array = array();
  foreach ($geolocations as $geolocation) {
    $geolocation_id = $geolocation->ID;
    $geolocation_title = get_the_title($geolocation_id);

    foreach ($titles_data['Byer'] as $city => $articles) {
      if ($geolocation_title == $city) {
        foreach ($titles_data['Byer'][$geolocation_title] as $seo_article_key => $seo_article_title) {
          update_post_meta($geolocation_id, "seoarticle: " . $seo_article_key, $seo_article_title);
        }
      }
    }
  }
}

function write_articles_for_geolocation($geolocation_id)
{
  //get all article titles for the geolocation
  $seo_article_1 = get_post_meta($geolocation_id, "seoarticle: SEO-artikel 1", true);
  $seo_article_2 = get_post_meta($geolocation_id, "seoarticle: SEO-artikel 2", true);
  $seo_article_3 = get_post_meta($geolocation_id, "seoarticle: SEO-artikel 3", true);
  $seo_article_4 = get_post_meta($geolocation_id, "seoarticle: SEO-artikel 4", true);
  $seo_article_5 = get_post_meta($geolocation_id, "seoarticle: SEO-artikel 5", true);
  $seo_article_6 = get_post_meta($geolocation_id, "seoarticle: SEO-artikel 6", true);


  //create an  array of the article titles
  $seo_articles = array($seo_article_1, $seo_article_2, $seo_article_3, $seo_article_4, $seo_article_5, $seo_article_6);

  //remove any empty array keys
  $seo_articles = array_filter($seo_articles);

  //write the articles
  foreach ($seo_articles as $seo_article_title) {
    //check if the seo article title already exists in wp posts
    $existing_post = get_page_by_title($seo_article_title, OBJECT, 'post');

    if ($existing_post) {
      trigger_error("The article " . $seo_article_title . " already exists", E_USER_WARNING);
      continue;
    }

    $article = generate_geolocation_chatgpt_seo_article($seo_article_title, get_the_title($geolocation_id));

    // Create a new post with the article contents and title. Use a html block for the content
    $post = array(
      'post_title'   => $seo_article_title,
      'post_content' => $article,
      'post_status'  => 'draft',
      'post_author'  => 10, //majken holm
      'post_type'    => 'post',
      'post_category' => array(139) // Use the ID of the category 'viden-raad'
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post);

    // Set tags for the post
    wp_set_post_tags($post_id, 'chatgpt geolocation seo article', true);

    //set the post category to geolocation-seo-article
    wp_set_post_categories($post_id, 157);

    //set the post meta key "geolocation" to the geolocation id
    update_post_meta($post_id, 'geolocation', $geolocation_id);

    //add the article id to the geolocation post meta "seo-articles"
    $seo_articles = get_post_meta($geolocation_id, 'seo_articles', false);
    $seo_articles[] = $post_id;
    update_post_meta($geolocation_id, 'seo_articles', $seo_articles);
  }
}

function generate_geolocation_chatgpt_seo_article($prompt, $geolocation_title)
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

  trigger_error("finished article: " . $prompt .  " " . $article, E_USER_NOTICE);

  trigger_error("generated chatgpt article", E_USER_NOTICE);

  return $article;
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


//get the JSON data
$titles = '{
    "Byer": {
      "København": {
        "SEO-artikel 1": "Skab plads i dit hjem med opbevaring i København",
        "SEO-artikel 2": "Opdag mulighederne inden for opmagasinering i København",
        "SEO-artikel 3": "Find dit næste depotrum i København",
        "SEO-artikel 4": "En guide til sikker opbevaring af dine møbler i København",
        "SEO-artikel 5": "Dit nye lagerrum venter i hjertet af København",
        "SEO-artikel 6": "Hvad koster et opbevaringsrum i København? En dybdegående redegørelse"
      },
      "Aarhus": {
        "SEO-artikel 1": "Tidsdeling af opbevaring i Aarhus - sådan fungerer det",
        "SEO-artikel 2": "Udforsk dine valg for opmagasinering i Aarhus",
        "SEO-artikel 3": "Aarhus tilbyder mange muligheder for depotrum",
        "SEO-artikel 4": "Opbevaring i Aarhus: Sådan holder du dine møbler i topform",
        "SEO-artikel 5": "Få ekstra plads med lagerrum til leje i Aarhus",
        "SEO-artikel 6": "Din ultimative vejledning til prisen på et opbevaringsrum i Aarhus"
      },
      "Odense": {
        "SEO-artikel 1": "Få styr på indretningen med opbevaring i Odense",
        "SEO-artikel 2": "Vejledning til opmagasinering i Odense",
        "SEO-artikel 3": "Opdag de bedste depotrum i Odense",
        "SEO-artikel 4": "Spar plads i hjemmet: Møbelopbevaring i Odense",
        "SEO-artikel 5": "Spar plads hjemme med lagerrum til leje i Odense",
        "SEO-artikel 6": "Undersøgelse af omkostningerne ved et opbevaringsrum i Odense"
      },
      "Aalborg": {
        "SEO-artikel 1": "Sikker opbevaring i Aalborg - din guide til løsningen",
        "SEO-artikel 2": "Find den rigtige løsning for opmagasinering i Aalborg",
        "SEO-artikel 3": "Aalborg er det bedste valg for depotrum",
        "SEO-artikel 4": "Opdag fordelene ved møbelopbevaring i Aalborg",
        "SEO-artikel 5": "Udvid dit hjem med lagerrum til leje i Aalborg",
        "SEO-artikel 6": "Indsigt i prisen på opbevaringsrum i Aalborg"
      },
      "Esbjerg": {
        "SEO-artikel 1": "Find den bedste løsning til opbevaring i Esbjerg",
        "SEO-artikel 2": "Effektiv opmagasinering i Esbjerg: En guide",
        "SEO-artikel 3": "Esbjerg tilbyder en nem og sikker guide til depotrum",
        "SEO-artikel 4": "Dine møbler fortjener den bedste opbevaring i Esbjerg",
        "SEO-artikel 5": "Få mere plads med lagerrum til leje i Esbjerg",
        "SEO-artikel 6": "En detaljeret analyse af hvad et opbevaringsrum koster i Esbjerg"
      },
      "Randers": {
        "SEO-artikel 1": "Gør det nemt for dig selv - opbevaring i Randers",
        "SEO-artikel 2": "Dine bedste muligheder for opmagasinering i Randers",
        "SEO-artikel 3": "Depotrum er gjort nemt og sikkert i Randers",
        "SEO-artikel 4": "Randers: Din løsning på opbevaring af møbler",
        "SEO-artikel 5": "Det ideelle lagerrum i Randers",
        "SEO-artikel 6": "Sådan finder du omkostningerne for et opbevaringsrum i Randers"
      },
      "Kolding": {
        "SEO-artikel 1": "Storage-løsninger i Kolding - Opbevaring for alle behov",
        "SEO-artikel 2": "Få styr på dine ting med opmagasinering i Kolding",
        "SEO-artikel 3": "Find de bedste priser for depotrum i Kolding",
        "SEO-artikel 4": "Opbevaring af møbler i Kolding: Din vej til et ryddeligt hjem",
        "SEO-artikel 5": "Din ekstra plads i Kolding - lagerrum til leje",
        "SEO-artikel 6": "Opdag hvad du skal betale for et opbevaringsrum i Kolding"
      },
      "Horsens": {
        "SEO-artikel 1": "Fleksible muligheder for opbevaring i Horsens",
        "SEO-artikel 2": "Gør plads i hjemmet med opmagasinering i Horsens",
        "SEO-artikel 3": "Horsens: Din løsning til sikre depotrum",
        "SEO-artikel 4": "Forstå vigtigheden af korrekt møbelopbevaring i Horsens",
        "SEO-artikel 5": "Opbevaring med omtanke - lagerrum til leje i Horsens",
        "SEO-artikel 6": "Horsens opbevaringsrum - hvad er prisen?"
      },
      "Vejle": {
        "SEO-artikel 1": "Lagerhotel eller opbevaring - Valget i Vejle",
        "SEO-artikel 2": "Overvejelser ved valg af opmagasinering i Vejle",
        "SEO-artikel 3": "Vejle tilbyder depotrum til overkommelige priser",
        "SEO-artikel 4": "Sikker og pålidelig opbevaring af møbler i Vejle",
        "SEO-artikel 5": "Lagerrum til leje - Vejle opbevaringsløsninger",
        "SEO-artikel 6": "Vejle opbevaringsrum omkostninger: en komplet guide"
      },
      "Roskilde": {
        "SEO-artikel 1": "Opbevaring i Roskilde - Sådan får du mest ud af din plads",
        "SEO-artikel 2": "Håndtering af opmagasinering i Roskilde",
        "SEO-artikel 3": "Find løsninger til depotrum i Roskilde",
        "SEO-artikel 4": "Forny dit liv med møbelopbevaring i Roskilde",
        "SEO-artikel 5": "Dit lager i Roskilde centrum",
        "SEO-artikel 6": "Besvarer spørgsmålet: Hvad koster et opbevaringsrum i Roskilde?"
      },
      "Herning": {
        "SEO-artikel 1": "Midlertidig opbevaring i Herning: Din guide til løsninger",
        "SEO-artikel 2": "Sikker opmagasinering i Herning: Din guide",
        "SEO-artikel 3": "Herning er stedet for depotrum du kan stole på",
        "SEO-artikel 4": "Herning står klar til at opbevare dine møbler",
        "SEO-artikel 5": "Lagerrum til leje: Oplev Hernings opbevaringstilbud",
        "SEO-artikel 6": "Hvor meget skal du afsætte til et opbevaringsrum i Herning"
      },
      "Silkeborg": {
        "SEO-artikel 1": "Billige opbevaringspladser i Silkeborg",
        "SEO-artikel 2": "Organiser dit liv med opmagasinering i Silkeborg",
        "SEO-artikel 3": "Silkeborg tilbyder depotrum til gode priser",
        "SEO-artikel 4": "Hold dit hjem ryddeligt med møbelopbevaring i Silkeborg",
        "SEO-artikel 5": "Dit private lager i naturskønne Silkeborg",
        "SEO-artikel 6": "Hvad er prislejet for et opbevaringsrum i Silkeborg?"
      },
      "Hørsholm": {
        "SEO-artikel 1": "Få peace of mind med sikker opbevaring i Hørsholm",
        "SEO-artikel 2": "Få overblik med opmagasinering i Hørsholm",
        "SEO-artikel 3": "Find dit næste depotrum i Hørsholm",
        "SEO-artikel 4": "Find den rette opbevaring til dine møbler i Hørsholm",
        "SEO-artikel 5": "Lagerrum til leje i Hørsholm - den rigtige opbevaringsløsning for dig",
        "SEO-artikel 6": "Hvad vil et opbevaringsrum koste dig i Hørsholm?"
      },
      "Helsingør": {
        "SEO-artikel 1": "Din guide til personlig opbevaring i Helsingør",
        "SEO-artikel 2": "Opmagasinering i Helsingør: En smart løsning",
        "SEO-artikel 3": "Helsingør: din guide til nemme depotrum",
        "SEO-artikel 4": "Sådan får du mere plads i hjemmet med møbelopbevaring i Helsingør",
        "SEO-artikel 5": "Hvordan leje lagerrum i Helsingør",
        "SEO-artikel 6": "Kosteovervejelser ved valg af opbevaringsrum i Helsingør"
      },
      "Næstved": {
        "SEO-artikel 1": "Få mest for pengene - opbevaring i Næstved",
        "SEO-artikel 2": "Alt hvad du bør vide om opmagasinering i Næstved",
        "SEO-artikel 3": "Næstved tilbyder depotrum til overkommelige priser",
        "SEO-artikel 4": "Spar tid og plads med møbelopbevaring i Næstved",
        "SEO-artikel 5": "Find de bedste lagerrum til leje i Næstved",
        "SEO-artikel 6": "Hvad koster et opbevaringsrum egentligt i Næstved?"
      },
      "Viborg": {
        "SEO-artikel 1": "Spar plads i dit hjem med opbevaring i Viborg",
        "SEO-artikel 2": "Styr på pladsen med opmagasinering i Viborg",
        "SEO-artikel 3": "Find løsninger til depotrum i Viborg",
        "SEO-artikel 4": "Opbevaring af møbler i Viborg: Sådan bevarer du dem i bedst mulig stand",
        "SEO-artikel 5": "Styr på opbevaring? Overvej lagerrum til leje i Viborg",
        "SEO-artikel 6": "Din guide til omkostninger ved et opbevaringsrum i Viborg"
      },
      "Fredericia": {
        "SEO-artikel 1": "Opbevaring i Fredericia: Hvad skal du overveje?",
        "SEO-artikel 2": "Den bedste løsning for opmagasinering i Fredericia",
        "SEO-artikel 3": "Fredericia: Find et depotrum",
        "SEO-artikel 4": "Dine måder at opbevare møbler i Fredericia",
        "SEO-artikel 5": "Overvejer du at leje lagerrum i Fredericia?",
        "SEO-artikel 6": "Indblik i prisen for et opbevaringsrum i Fredericia"
      },
      "Køge": {
        "SEO-artikel 1": "Opbevaring i Køge - den komplette guide",
        "SEO-artikel 2": "Fleksible muligheder for opmagasinering i Køge",
        "SEO-artikel 3": "Køge tilbyder depotrum til gode priser",
        "SEO-artikel 4": "Vælg den rette møbelopbevaring i Køge",
        "SEO-artikel 5": "Find dit ideelle lagerrum i Køge",
        "SEO-artikel 6": "Hvad skal du ofre for et opbevaringsrum i Køge?"
      },
      "Holstebro": {
        "SEO-artikel 1": "Opbevaring i Holstebro: Hvordan du finder den bedste løsning",
        "SEO-artikel 2": "Dit overblik over opmagasinering i Holstebro",
        "SEO-artikel 3": "Find dit næste depotrum i Holstebro",
        "SEO-artikel 4": "Gør plads i dit hjem med opbevaring af møbler i Holstebro",
        "SEO-artikel 5": "Hold dit hjem ryddeligt med lagerrum til leje i Holstebro",
        "SEO-artikel 6": "Grundig gennemgang af priserne på opbevaringsrum i Holstebro"
      },
      "Taastrup": {
        "SEO-artikel 1": "Smart opbevaring i Taastrup - Sådan gør du",
        "SEO-artikel 2": "Plads til det hele med opmagasinering i Taastrup",
        "SEO-artikel 3": "Taastrup er din guide til nemme depotrum",
        "SEO-artikel 4": "Taastrup: En by klar til at tage hånd om din møbelopbevaring",
        "SEO-artikel 5": "Få mere plads med lagerrum til leje i Taastrup",
        "SEO-artikel 6": "Lær mere om prisen på et opbevaringsrum i Taastrup"
      },
      "Slagelse": {
        "SEO-artikel 1": "Tryg og sikker opbevaring i Slagelse",
        "SEO-artikel 2": "Alt inden for opmagasinering i Slagelse",
        "SEO-artikel 3": "Slagelse tilbyder depotrum til overkommelige priser",
        "SEO-artikel 4": "Slagelse tilbyder sikker opbevaring af dine møbler",
        "SEO-artikel 5": "Gør plads til det vigtige med lagerrum til leje i Slagelse",
        "SEO-artikel 6": "Find ud af hvad et opbevaringsrum i Slagelse koster"
      },
      "Hillerød": {
        "SEO-artikel 1": "Optimal udnyttelse af plads med opbevaring i Hillerød",
        "SEO-artikel 2": "Få plads til mere med opmagasinering i Hillerød",
        "SEO-artikel 3": "Find løsninger til depotrum i Hillerød",
        "SEO-artikel 4": "Styrk livsstilen: Perfekt møbelopbevaring i Hillerød",
        "SEO-artikel 5": "Sikker opbevaring? Det er muligt med lagerrum til leje i Hillerød",
        "SEO-artikel 6": "Udforskning af opbevaringsrumspriser i Hillerød"
      },
      "Holbæk": {
        "SEO-artikel 1": "Din komplette guide til opbevaring i Holbæk",
        "SEO-artikel 2": "Råd om opmagasinering i Holbæk",
        "SEO-artikel 3": "Find et depotrum i Holbæk",
        "SEO-artikel 4": "Holbæk: Din betroede partner i opbevaring af møbler",
        "SEO-artikel 5": "Opdag de mange fordele ved lagerrum til leje i Holbæk",
        "SEO-artikel 6": "Hvad koster det at have et opbevaringsrum i Holbæk?"
      },
      "Sønderborg": {
        "SEO-artikel 1": "Opbevaring i Sønderborg - skab mere plads i dit hjem",
        "SEO-artikel 2": "Stressfri opmagasinering i Sønderborg",
        "SEO-artikel 3": "Sønderborg tilbyder depotrum til gode priser",
        "SEO-artikel 4": "Sønderborg sikrer den bedste opbevaring til dine møbler",
        "SEO-artikel 5": "Sønderborgs fremragende tilbud: Lagerrum til leje",
        "SEO-artikel 6": "Få styr på udgifterne til opbevaringsrum i Sønderborg"
      },
      "Nordvest": {
        "SEO-artikel 1": "Tilbud på al slags opbevaring i Nordvest",
        "SEO-artikel 2": "Hold styr på dine sager med opmagasinering i Nordvest",
        "SEO-artikel 3": "Find dit næste depotrum i Nordvest",
        "SEO-artikel 4": "Få mere plads med møbelopbevaring i Nordvest",
        "SEO-artikel 5": "Eftertragtede lagerrum til leje i Nordvest",
        "SEO-artikel 6": "Afdækning af opbevaringsrumsudgifter i Nordvest"
      },
      "Valby": {
        "SEO-artikel 1": "Alt du skal vide om opbevaring i Valby",
        "SEO-artikel 2": "Praktiske løsninger til opmagasinering i Valby",
        "SEO-artikel 3": "Valby er din guide til nemme depotrum",
        "SEO-artikel 4": "Sikker og pålidelig opbevaring af møbler i Valby",
        "SEO-artikel 5": "Få styr på dit rod med lagerrum til leje i Valby",
        "SEO-artikel 6": "Bliv klogere på prisen for et opbevaringsrum i Valby"
      },
      "Østerbro": {
        "SEO-artikel 1": "Gør plads til livet med opbevaring i Østerbro",
        "SEO-artikel 2": "Optimer dit pladsforbrug med opmagasinering i Østerbro",
        "SEO-artikel 3": "Østerbro tilbyder depotrum til overkommelige priser",
        "SEO-artikel 4": "Østerbro: En by rig på løsninger for møbelopbevaring",
        "SEO-artikel 5": "Ekstra plads direkte i dit nabolag: Lagerrum til leje i Østerbro",
        "SEO-artikel 6": "Hvad koster et opbevaringsrum i Østerbro? Vi finder svaret!"
      },
      "Amager": {
        "SEO-artikel 1": "Skab orden i kaos med opbevaring på Amager",
        "SEO-artikel 2": "Sikker og nem opmagasinering i Amager",
        "SEO-artikel 3": "Find løsninger til depotrum i Amager",
        "SEO-artikel 4": "Smarte opbevaringsløsninger for møbler i Amager",
        "SEO-artikel 5": "Spar på pladsen med lagerrum til leje på Amager",
        "SEO-artikel 6": "Økonomisk overblik: Pris på opbevaringsrum i Amager"
      },
      "Kastrup": {
        "SEO-artikel 1": "De bedste opbevaringsmuligheder i Kastrup",
        "SEO-artikel 2": "Lær mere om opmagasinering i Kastrup",
        "SEO-artikel 3": "Kastrup er stedet for depotrum du kan stole på",
        "SEO-artikel 4": "Kastrup: Gør plads til mere med vores møbelopbevaring",
        "SEO-artikel 5": "Undgå rod i hverdagen med lagerrum til leje i Kastrup",
        "SEO-artikel 6": "Gennemgang: Hvad koster et opbevaringsrum i Kastrup?"
      },
      "Vesterbro": {
        "SEO-artikel 1": "Sådan vælger du opbevaring på Vesterbro",
        "SEO-artikel 2": "Kvalitets opmagasinering i Vesterbro",
        "SEO-artikel 3": "Vesterbro tilbyder depotrum til gode priser",
        "SEO-artikel 4": "Dine møbler får det bedste hjem i Vesterbro",
        "SEO-artikel 5": "Er dit hjem i Vesterbro trængt for plads? Prøv lagerrum til leje",
        "SEO-artikel 6": "Få svaret: Hvad er prisen for et opbevaringsrum i Vesterbro?"
      },
      "Ørestad": {
        "SEO-artikel 1": "Opbevaring i Ørestad - Din moderne løsning",
        "SEO-artikel 2": "Pladsbesparende opmagasinering i Ørestad",
        "SEO-artikel 3": "Find dit næste depotrum i Ørestad",
        "SEO-artikel 4": "Ørestad: Din ideelle løsning på opbevaring af møbler",
        "SEO-artikel 5": "Gør hverdagen lettere med lagerrum til leje i Ørestad",
        "SEO-artikel 6": "Hvor dyrt er et opbevaringsrum i Ørestad?"
      },
      "Vanløse": {
        "SEO-artikel 1": "Fordele og ulemper ved opbevaring i Vanløse",
        "SEO-artikel 2": "Opmagasinering i Vanløse: Sådan gør du",
        "SEO-artikel 3": "Vanløse er din guide til nemme depotrum",
        "SEO-artikel 4": "Vanløse: Få plads til livet med vores møbelopbevaring",
        "SEO-artikel 5": "Nyd godt af et ekstra lagerrum i Vanløse",
        "SEO-artikel 6": "Prisen på et opbevaringsrum i Vanløse"
      },
      "Nørrebro": {
        "SEO-artikel 1": "Skab mere plads i dit hjem med opbevaring i Nørrebro",
        "SEO-artikel 2": "Din guide til opmagasinering i Nørrebro",
        "SEO-artikel 3": "Nørrebro tilbyder depotrum til overkommelige priser",
        "SEO-artikel 4": "Optimer dit hjem med møbelopbevaring i Nørrebro",
        "SEO-artikel 5": "Gør plads til det vigtige i Nørrebro med lagerrum til leje",
        "SEO-artikel 6": "Få overblik over prisen på et opbevaringsrum i Nørrebro"
      },
      "Nordhavn": {
        "SEO-artikel 1": "Cyber Monday tilbud: opbevaring i Nordhavn",
        "SEO-artikel 2": "Skab plads med opmagasinering i Nordhavn",
        "SEO-artikel 3": "Find løsninger til depotrum i Nordhavn",
        "SEO-artikel 4": "Lever mere med mindre: møbelopbevaring i Nordhavn",
        "SEO-artikel 5": "Komfortabel opbevaring med lagerrum til leje i Nordhavn",
        "SEO-artikel 6": "Undersøgelse: Hvad koster et opbevaringsrum i Nordhavn?"
      },
      "Frederiksberg": {
        "SEO-artikel 1": "Oplagring i Frederiksberg - Sådan skaber du plads i dit hjem",
        "SEO-artikel 2": "Vekslende opmagasinering i Frederiksberg",
        "SEO-artikel 3": "Frederiksberg er stedet for depotrum du kan stole på",
        "SEO-artikel 4": "Frederiksberg: Dit stop for sikker møbelopbevaring",
        "SEO-artikel 5": "Få mere luft i hjemmet med lagerrum til leje i Frederiksberg",
        "SEO-artikel 6": "Så meget koster et opbevaringsrum i Frederiksberg"
      },
      "Dragør": {
        "SEO-artikel 1": "Guide til sikker opbevaring i Dragør",
        "SEO-artikel 2": "Opmagasinering i Dragør: En guide til smartere opbevaring",
        "SEO-artikel 3": "Dragør tilbyder depotrum til gode priser",
        "SEO-artikel 4": "Dragør står klar til at opbevare dine møbler",
        "SEO-artikel 5": "Dragør byder velkommen til dit nye lagerrum.",
        "SEO-artikel 6": "Din guide til omkostningerne ved et opbevaringsrum i Dragør"
      },
      "Brønshøj": {
        "SEO-artikel 1": "Sådan fungerer opbevaring i Brønshøj.",
        "SEO-artikel 2": "Bedste tips til opmagasinering i Brønshøj",
        "SEO-artikel 3": "Find dit næste depotrum i Brønshøj",
        "SEO-artikel 4": "Bevar dine møbler i bedste stand med opbevaring i Brønshøj",
        "SEO-artikel 5": "Find dit personlige lagerrum i Brønshøj.",
        "SEO-artikel 6": "Hvad skal du betale for et opbevaringsrum i Brønshøj?"
      }
    }
  }';
