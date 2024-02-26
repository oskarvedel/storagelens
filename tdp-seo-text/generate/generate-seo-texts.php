<?php

function generate_seo_texts()
{
     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     foreach ($geolocations as $geolocation) {
          $geolocation_id = $geolocation->ID;

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

          $num_of_seo_gd_places = count($seo_gd_place_list);

          $seo_num_of_units_available = get_post_meta($geolocation_id, 'seo_num_of_units_available', true);

          //set variables
          global $statistics_data_fields;
          global $meta_title_candidates;
          global $first_paragraph_candidates;
          global $second_paragraph_candidates;
          global $description_title_candidates;
          global $second_paragraph;
          global $price_table;
          global $third_paragraph;
          $output = '<div class="seo-text">';
          global $statistics_data_fields_texts;

          $description = get_post_meta($geolocation_id, 'description', true);

          //add content to output
          if ($description) {
               $output .= get_seo_text($archive_title_trimmed, $description_title_candidates);
               $output .= ' <p class="three-columns">[map img]<br>' . $description . '</p>';
          }


          //if no gd places, use basic text
          if ($num_of_seo_gd_places <= 2) {
               global $basic_text;
               $output = $basic_text;
               $output = str_replace("[location]", $archive_title_trimmed, $output);
               update_post_meta($geolocation_id, 'seo_text', $output);
               $output .= '</div>'; //add closing div for seo-text class div
               continue;
          }

          //if succifient gd places, use non-basic text
          $output .= '<hr class="line">';
          $output .= get_seo_text($archive_title_trimmed, $first_paragraph_candidates);
          $output .= '<hr class="line">';
          $output .= generate_price_table($geolocation_id);
          $output .= '<hr class="line">';
          $output .= get_seo_text($archive_title_trimmed, $second_paragraph_candidates);
          $output .= '<hr class="line">';
          $output .= $third_paragraph;
          $output .= '<hr class="line">';
          //$output .= generate_schools_paragraph($geolocation_id);
          //$output .= generate_neighbourhoods_paragraph($geolocation_id);
          $output .= generate_selfstorage_provider_list($geolocation_id);

          //relace variable placeholders with data
          $output = replace_variable_placeholders($output, $statistics_data_fields, $geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available, $archive_title_trimmed);
          $old_seo_text = get_post_meta($geolocation_id, 'seo_text', true);

          $output .= '</div>'; //add closing div for seo-text class div

          if ($old_seo_text != $output) {
               update_post_meta($geolocation_id, 'seo_text', $output);
               trigger_error("SEO text updated for " . $archive_title_trimmed, E_USER_NOTICE);
          }
     }
     trigger_error("SEO texts updated", E_USER_NOTICE);
}

function replace_variable_placeholders($input_text, $statistics_data_fields, $geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available, $archive_title_trimmed)
{
     $input_text = str_replace("[num_of_seo_gd_places]", $num_of_seo_gd_places, $input_text);

     $input_text = str_replace("[seo_num_of_units_available]", $seo_num_of_units_available, $input_text);

     $input_text = str_replace("[location]", $archive_title_trimmed, $input_text);

     $input_text = replace_statistics_data_fields_with_values($input_text, $statistics_data_fields, $geolocation_id);

     $input_text = replace_image_variables($input_text, $geolocation_id, $archive_title_trimmed);

     return $input_text;
}


function replace_image_variables($input_text, $geolocation_id, $archive_title_trimmed)
{
     //insert static map
     $static_map = get_post_meta($geolocation_id, 'static_map', true);
     $input_text = str_replace("[map img]", '<img src="' . $static_map . '" alt="Kort over ' . $archive_title_trimmed . '" class="map-img">', $input_text);
     return $input_text;
}
function replace_statistics_data_fields_with_values($input_text, $statistics_data_fields, $geolocation_id)
{
     foreach ($statistics_data_fields as $field) {
          $value = get_post_meta($geolocation_id, $field, true);
          if (!empty($value)) {
               $rounded = floatval(round($value, 2));
               $numberformat = number_format($value, 0, ',', '.');
               $input_text = str_replace("[$field]", $numberformat, $input_text);
          } else {
               $input_text = str_replace("[$field]", "Ukendt", $input_text);
          }
     }
     return $input_text;
}

function generate_selfstorage_provider_list($geolocation_id)
{
     $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

     usort($seo_gd_place_list, function ($a, $b) {
          $partnerA = get_post_meta($a, 'partner', true);
          $partnerB = get_post_meta($b, 'partner', true);
          $showA = get_post_meta($a, 'show_listing', true);
          $showB = get_post_meta($b, 'show_listing', true);

          if ($partnerA == 1 && $partnerB != 1) {
               return -1;
          } elseif ($partnerA != 1 && $partnerB == 1) {
               return 1;
          } elseif ($showA == 1 && $showB != 1) {
               return -1;
          } elseif ($showA != 1 && $showB == 1) {
               return 1;
          } else {
               return 0;
          }
     });

     if (!empty($seo_gd_place_list)) {
          $return_text = '<h4>Der er i alt [num_of_seo_gd_places] udbydere af depotrum i og omkring [location]:</h4>';
          $return_text .= '<p class="three-columns gd_place_list"><small>';
          foreach ($seo_gd_place_list as $gd_place) {
               $place_name = get_the_title($gd_place);
               $place_url = get_permalink($gd_place);
               $partner = get_post_meta($gd_place, 'partner', true);
               $show = get_post_meta($gd_place, 'show_listing', true);
               if ($partner) {
                    $return_text .=  '<a href="' . $place_url . '" class="partner_gd_place_link">' . $place_name . '</a><br>';
               } elseif ($show) {
                    $return_text .=  '<a href="' . $place_url . '" class="gd_place_link">' . $place_name . '</a><br>';
               } else {
                    $return_text .=  $place_name . '<br>';
               }
          }
          $return_text .= '</small></ul>';
          return $return_text;
     }
}

$second_paragraph = '<h2>Find opbevaring i [location]</h2>
     <p class="three-columns">Hvis du leder efter opbevaring i [location], er du kommet til det rette sted. Her på tjekdepot har vi registreret [num_of_seo_gd_places] udbydere af opbevaring placeret i [location], og de tilbyder alle sikre og tilgængelige depotrum. Du kan sortere alle depotrum i dit område efter pris, størrelse og egenskaber. Her kan du også finde mere information om de forskellige egenskaber ved et depotrum såsom klimakontrol, adgangsforhold og sikkerhedsforanstaltninger. Se vores opslag i [location], vælg et depotrum, der passer til dine behov, og lej den med det samme. Når du reserverer opbevaring, har du et rigtig godt sted at placere dine ting, både på lang og kort sigt. Langt de fleste opbevaringsrum udlejes på månedsbasis, hvilket betyder, at du har mulighed for at flytte ind og ud når som helst. Du kan leje opbevaring i [location] i en måned eller et helt år - uanset hvad, kan du forlænge din lejeperiode og skifte til et depotrum på en anden størrelse, når du ønsker det. På denne måde er det nemt at vælge den bedste opbevaring til dine ejendele!</p>';

function generate_price_table($geolocation_id)
{
     $size_rows = array(
          'mini' => '
               <tr>
               <td class="seo-table-mini">Mini (fra 0 til 2 m²)</td>
               <td class="right-align">[mini size lowest price]</td>
               <td class="right-align">[mini size average price]</td>
               <td class="right-align">[mini size highest price]</td>
               </tr>',
          'small' => '
               <tr>
               <td class="seo-table-small">Lille (2 til 7 m²)</td>
               <td class="right-align">[small size lowest price]</td>
               <td class="right-align">[small size average price]</td>
               <td class="right-align">[small size highest price]</td>
               </tr>',
          'medium' => '
               <tr>
               <td class="seo-table-medium">Mellem (7 til 18 m²)</td>
               <td class="right-align">[medium size lowest price]</td>
               <td class="right-align">[medium size average price]</td>
               <td class="right-align">[medium size highest price]</td>
               </tr>',
          'large' => '
               <tr>
               <td class="seo-table-large">Stort (18 til 25 m²)</td>
               <td class="right-align">[large size lowest price]</td>
               <td class="right-align">[large size average price]</td>
               <td class="right-align">[large size highest price]</td>
               </tr>',
          'very_large' => '
               <tr>
               <td class="seo-table-very-large">Meget stort (over 25 m²)</td>
               <td class="right-align">[very large size lowest price]</td>
               <td class="right-align">[very large size average price]</td>
               <td class="right-align">[very large size highest price]</td>
               </tr>'
     );

     $price_table = '
               <h3>Priser på depotrum i [location]</h3>
               <table>
               <thead>
               <tr>
               <th class="left-align seo-table-size"><strong>Størrelse</strong></th>
               <th class="right-align seo-table-lowest-price"><strong>Laveste pris</strong></th>
               <th class="right-align seo-table-average-price"><strong>Gennemsnitspris</strong></th>
               <th class="right-align seo-table-highest-price"><strong>Højeste pris</strong></th>
               </tr>
               </thead>
               <tbody>';

     $size_rows_to_include = '';
     foreach ($size_rows as $size => $size_row) {
          if (check_if_price_table_fields_exist($size, $geolocation_id)) {
               $size_rows_to_include .= $size_row;
          }
     }

     if (empty($size_rows_to_include)) {
          return '';
     }

     $price_table .= $size_rows_to_include;

     $price_table .=
          '</tbody>
          </table>';

     return $price_table;
}

function check_if_price_table_fields_exist($size, $geolocation_id)
{
     $price_table_fields = array(
          $size . ' size lowest price',
          $size . ' size average price',
          $size . ' size highest price',
     );
     foreach ($price_table_fields as $field => $size_field) {
          $value = get_post_meta($geolocation_id, $size_field, true);
          if (empty($value)) {
               return false;
          }
     }
     return true;
}

$third_paragraph = '<h2>Hvordan finder jeg et sted at opmagasinere mine ting?</h2>
     <p class="three-columns">Hvis du leder efter et sikkert sted at opbevare mindre brugte genstande og personlige ejendele af alle typer og størrelser, er det oplagt at bruge opmagasinering, depotrum eller et opbevaringsrum tæt på dit hjem eller din virksomhed, så du har adgang til dine ting når som helst uden besvær. Her på tjekdepot får du et overblik over priser på opmagasinering og opbevaring og over rumstørrelser, egenskaber og fordele afhængigt af dine behov og dit budget. Du kan nemt finde opmagasinering i dit nabolag eller din by, uanset hvor du befinder dig i Danmark. Du er kun én søgning væk fra at finde det rigtige sted til dine ejendele i [location].</p>';

function generate_schools_paragraph($geolocation_id)
{
     $seo_schools = get_post_meta($geolocation_id, 'seo_schools', true);

     $schools_first_paragraph = '<h2>Udvid din studiebolig i [location] med et depotrum</h2>
     <p class="three-columns">Hvis du skal flytte til [location] for at studere studere på ';
     $schools_second_paragraph = 'kan du få mere plads i din studiebolig med et depotrum. Studieboliger har ofte meget begrænset plads, og du kan undgå at rod af tøj og bøger, du ikke bruger hele året, ligger og roder. Et depotrum kan også være en idé for internationale studerende, der skal have opbevaret deres ejendele imens de er hjemme på sommerferie.</p>';
     if (!empty($schools)) {
          $schools_array = explode(",", $schools);
          $return_text = '';
          $return_text .=  $schools_first_paragraph;
          foreach ($schools_array as $key => $school) {
               if ($key === count($schools_array) - 2) {
                    $return_text .=  $school  . ' eller ';
               } elseif ($key === count($schools_array) - 1) {
                    $return_text .=  $school . ' ';
               } else {
                    $return_text .=  $school  . ', ';
               }
          }
          $return_text .=  $schools_second_paragraph;
          $return_text .= '<hr class="line">';
          return $return_text;
     }
}

function generate_neighbourhoods_paragraph($geolocation_id)
{
     $neighbourhoods = get_post_meta($geolocation_id, 'geodir_neighbourhoods', false);
     $neighbourhoods_first_paragraph = '<h2>Bydele i Åarhus</h2>';
     if (!empty($neighbourhoods)) {
          $return_text = '<p>';
          $return_text .=  $neighbourhoods_first_paragraph;
          foreach ($neighbourhoods as $key => $sublocation) {
               $title = '<strong>' . get_the_title($sublocation) . '</strong>';
               $description = str_replace('<p>', '', get_the_content(null, false, $sublocation));
               $return_text .= $title . $description;
          }
          $return_text .= '</p>';
          $return_text .= '<hr class="line">';
          return $return_text;
     }
}

$text_template = '
          <p>[average price]
          [smallest m2 size]
          [lowest price]
          [average m2 price]
          [average m3 price]</p>
          <h3>Priser på depotrum i [location] fordelt efter størrelse</h3>
          <p>[mini size average price]
          [small size average price]
          [medium size average price]
          [large size average price]
          [very large size average price]</p>
           ';


$statistics_data_fields_texts = array(
     'average price' => 'Den gennemsnitlige pris for et ledigt depotrum i [location] er <strong>[average price] kr.</strong>',
     'smallest m2 size' => 'Der er er lige nu ledige depotrum fra <strong>[smallest m2 size] m² op til [largest m2 size] m², </strong>',
     'lowest price' => 'og prisen er mellem <strong>[lowest price] kr og [highest price] kr.</strong>',
     'average m2 price' => 'Kvadratmeterprisen er i gennemsnit <strong>[average m2 price] kr/m²,</strong>  og ',
     'average m3 price' => 'kubikmeterprisen er i gennemsnit <strong>[average m3 price] kr/m³.</strong>',
     'mini size average price' =>  'Et mini depotrum (op til 2 m²) koster i gennemsnit: <strong>[mini size average price] kr. </strong>',
     'small size average price' => 'Et lille depotrum (mellem 2 og 7 m²) koster i gennemsnit: <strong>[small size average price] kr. </strong>',
     'medium size average price' =>  'Et mellem depotrum (mellem 7 og 18 m²) koster i gennemsnit: <strong>[medium size average price] kr. </strong>',
     'large size average price' => 'Et stort depotrum (mellem 18 og 25 m²) koster i gennemsnit: <strong>[large size average price] kr. </strong>',
     'very large size average price' => 'Et meget stort depotrum (over 25 m²) koster i gennemsnit: <strong>[very large size average price] kr. </strong>',
);

function get_seo_text($location_title, $paragraph_array)
{
     $num_of_options = count($paragraph_array);
     $chosen_option = seeded_rand(1, $num_of_options, $location_title);
     return $paragraph_array[$chosen_option - 1];
}

function seeded_rand($min, $max, $seed)
{
     // Convert the seed to an integer using the crc32 function
     $seed = crc32($seed);

     // Seed the random number generator
     mt_srand($seed);

     // Generate and return a random number
     return mt_rand($min, $max);
}
