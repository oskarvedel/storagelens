<?php


function generate_default_unit_list_for_all_gd_places()
{
    $gd_places = get_posts(array(
        'post_type' => 'gd_place',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    foreach ($gd_places as $gd_place) {
        $gd_place_id = $gd_place->ID;

        //generate archive unit list
        $default_archive_page_unit_list = generate_default_unit_list_for_single_gd_place($gd_place_id, 1);
        if ($default_archive_page_unit_list) {
            update_post_meta($gd_place_id, 'default_archive_page_unit_list', $default_archive_page_unit_list);
        }
        //generate department unit list
        $default_department_page_unit_list = generate_default_unit_list_for_single_gd_place($gd_place_id, 0);
        if ($default_department_page_unit_list) {
            update_post_meta($gd_place_id, 'default_department_page_unit_list', $default_department_page_unit_list);
        }
    }
    trigger_error("Default unit lists updated for all gd_places", E_USER_NOTICE);
}

function generate_default_unit_list_for_single_gd_place($gd_place_id, $isArchivePage)
{

    $show_units = get_post_meta($gd_place_id, 'show_units', true);
    if (!$show_units) {
        return '';
    }

    $unit_items = get_post_meta($gd_place_id, 'depotrum', false);
    if (!$unit_items) {
        return '';
    }

    // check if each unit item is available
    $available_unit_items = [];
    foreach ($unit_items as $unit_item) {
        if (get_post_meta($unit_item, 'available', true)) {
            array_push($available_unit_items, $unit_item);
        }
    }

    $enable_booking = get_post_meta($gd_place_id, 'enable_booking', true);

    if ($enable_booking) {
        // xdebug_break();
    }
    $enable_direct_booking = get_post_meta($gd_place_id, 'enable_direct_booking', true);

    if ($available_unit_items && !empty($available_unit_items) && $show_units) {
        $gd_place_title = get_the_title($gd_place_id);
        $partner = get_post_meta($gd_place_id, 'partner', true);
        $num_of_available_units = get_post_meta($gd_place_id, 'num of units available', true);
        if ($num_of_available_units != 0) {
            // xdebug_break();
        }
        $permalink = get_permalink($gd_place_id);

        $finalOutput = '';
        $finalOutput .= generate_unit_list($finalOutput, $partner, $gd_place_id, $available_unit_items, $permalink, $enable_booking, $enable_direct_booking, $isArchivePage);
        $finalOutput .= generate_view_all_button($permalink, $partner, $num_of_available_units, $gd_place_title, $isArchivePage);
        return $finalOutput;
    }
}

function generate_unit_list($finalOutput, $partner, $lokationId, $available_unit_items, $permalink, $enable_booking, $enable_direct_booking, $isArchivePage = 0)
{
    $sorted_ids = [];
    try {
        $sorted_ids = sort_depotrum_by_price($available_unit_items);
    } catch (Exception $e) {
        try {
            $sorted_ids = sort_depotrum_by_m2_size($available_unit_items);
        } catch (Exception $e) {
            $sorted_ids = sort_depotrum_by_m3_size($available_unit_items);
        }
    }

    if ($isArchivePage) {
        $sorted_ids = extract_evenly_spaced($sorted_ids, 3);
    }

    if ($isArchivePage) {
        $finalOutput .= '<div class="depotrum-list no-border">';
    } else {
        $finalOutput .= '<div class="depotrum-list">';
    }

    $OutputArray = [];
    $output = '';
    $lastElement = end($sorted_ids);
    foreach ($sorted_ids as $depotrum) {
        $id = $depotrum->id;
        $relTypeId = getRelTypeId_unitlist($id);
        $unit_type = get_post_meta($relTypeId, 'unit_type', true);
        $m2 = get_post_meta($relTypeId, 'm2', true);

        //replace any "," in m2 strings with "."
        if (is_string($m2)) {
            $m2 = str_replace(',', '.', $m2);
        }
        //convert any m2 strings to floats
        if (is_string($m2)) {
            $m2 = floatval($m2);
        }

        $m3 = get_post_meta($relTypeId, 'm3', true);

        //replace any "," in m3 strings with "."
        if (is_string($m3)) {
            $m3 = str_replace(',', '.', $m3);
        }

        //convert any m3 strings to floats
        if (is_string($m3)) {
            $m3 = floatval($m3);
        }

        $available_date = get_post_meta($id, 'available_date', true);
        $booking_link = get_post_meta($id, 'booking_link', true);

        $container_type = get_post_meta($relTypeId, 'container_type', true);
        $isolated_container = get_post_meta($relTypeId, 'isolated_container', true);
        $ventilated_container = get_post_meta($relTypeId, 'ventilated_container', true);
        $price = get_post_meta($id, 'price', true);
        $price_per_m3_per_month = get_post_meta($id, 'price_per_m3_per_month', true);

        $output = '<div class="outer-depotrum-row">';

        if ($isArchivePage) {
            if ($partner) {
                if ($depotrum === $lastElement) {
                    $output .= '<a href="' . $permalink . '" class="depotrum-row yellowhover partner last" >';
                } else {
                    $output .= '<a href="' . $permalink . '" class="depotrum-row yellowhover partner">';
                }
            } else {
                if ($depotrum === $lastElement) {
                    $output .= '<div class="depotrum-row non-partner last">';
                } else {
                    $output .= '<div class="depotrum-row non-partner">';
                }
            }
        } else {
            if ($partner) {
                if ($depotrum === $lastElement) {
                    $output .= '<div class="depotrum-row partner yellowhover last">';
                } else {
                    $output .= '<div class="depotrum-row yellowhover partner">';
                }
            } else {
                if ($depotrum === $lastElement) {
                    $output .= '<div class="depotrum-row non-partner last">';
                } else {
                    $output .= '<div class="depotrum-row non-partner">';
                }
            }
        }

        if ($enable_booking && !$isArchivePage) {
            $output .= '<div class="flex-container booking-enabled" onclick="toggleFold(' . $id . ')">';
        } else {
            $output .= '<div class="flex-container">';
        }

        $output .= generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type, $isolated_container, $partner);
        $output .= generate_unit_desc_column($relTypeId, $unit_type, $m2, $m3, $container_type, $isolated_container, $partner, $enable_booking, $isArchivePage);
        if ($enable_booking && !$isArchivePage) {
            $output .= '<div class="flex-container-2 booking-enabled">';
        } else {
            $output .= '<div class="flex-container-2">';
        }
        $output .= generate_price_column($price, $price_per_m3_per_month, $partner, $isArchivePage, $enable_booking);


        $output .= generate_navigation_column($partner, $id, $enable_booking, $isArchivePage);
        $output .= '</div>';
        $output .= '</div>';

        if ($isArchivePage) {
            if ($partner) {
                $output .= '</a>';
            } else {
                $output .= '</div>';
            }
        } else {
            $output .= '</div>';
        }

        if ($partner && !$isArchivePage && $enable_booking) {
            $output .=  generate_booking_form($id, $available_date, $booking_link, $enable_direct_booking);
        }

        $output .= '</div>';

        array_push($OutputArray, $output);
    }
    foreach ($OutputArray as $arrayItem) {
        $finalOutput .= $arrayItem;
    }
    $finalOutput .= "</div>";

    return $finalOutput;
}

function generate_view_all_button($permalink, $partner, $num_of_available_units, $gd_place_title, $isArchivePage = 0)
{
    if ($isArchivePage && $partner) {
        if ($num_of_available_units > 8) {
            $finalOutput = '<a  href="' . $permalink . '"><div class="view-all-row yellowhover"><p class="view-all">Se alle ' . $num_of_available_units . ' ledige rum hos ' . $gd_place_title . '</p><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="22" height="22"><path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" /></svg></div></a>';
        } else {
            $finalOutput = '<a  href="' . $permalink . '"><div class="view-all-row yellowhover"><p class="view-all">Se alle ledige rum</p><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="22" height="22"><path d="M7.293 4.707 14.586 12l-7.293 7.293 1.414 1.414L17.414 12 8.707 3.293 7.293 4.707z" /></svg></div></a>';
        }
        return $finalOutput;
    } else {
        return '';
    }
}

function generate_navigation_column($partner, $unit_id, $enable_booking, $isArchivePage)
{
    if (!$isArchivePage && $partner && $enable_booking) { //listing page + partner
        return '<div class="continue-button" id="continue-button-' .  $unit_id . '">Fortsæt</div>';
    }
}

function generate_price_column($price, $price_per_m3_per_month, $partner, $isArchivePage, $enable_booking)
{
    if ($enable_booking && !$isArchivePage) {
        $output = '<div class="price-column vertical-center booking-enabled">';
    } else {
        $output = '<div class="price-column vertical-center">';
    }
    if ($price_per_m3_per_month) {
        if ($price_per_m3_per_month && $partner) {
            $output .= '<span class="price partner">' . round($price_per_m3_per_month, 2) . ' kr. / m³</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="month">pr. måned</span>';
        } else if ($price_per_m3_per_month && $partner != 1) {
            $output .= '<span class="price non-partner">' . round($price_per_m3_per_month, 2) . ' kr. / m³</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="month">pr. måned</span>';
        } else {
            $output .= '<span class="month">Pris ukendt</span>';
        }
    } else {
        if ($price && $partner) {
            $output .= '<span class="price partner">' . round($price, 2) . ' kr.</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="month">pr. måned</span>';
        } else if ($price && $partner != 1) {
            $output .= '<span class="price non-partner">' . round($price, 2) . ' kr.</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="month">pr. måned</span>';
        } else {
            $output .= '<span class="month">Pris ukendt</span>';
        }
    }
    $output .= '</div>';
    return $output;
}

function generate_unit_desc_column($relTypeId, $unit_type, $m2, $m3, $container_type, $isolated_container, $partner, $enable_booking, $isArchivePage)
{
    if ($enable_booking && !$isArchivePage) {
        $output = '<div class="size-column vertical-center booking-enabled">';
    } else {
        $output = '<div class="size-column vertical-center">';
    }

    if ($unit_type == "container") {
        if ($container_type == "8 feet") {
            $title = "8-fods container";
            $desc = $title;
            if ($isolated_container) {
                $title = "8-fods container (Isoleret)";
                $desc = "Isoleret 8-fods container";
            }
        } else if ($container_type == "10 feet") {
            $title = "10-fods container";
            $desc = $title;
            if ($isolated_container) {
                $title = "10-fods container (Isoleret)";
                $desc = "Isoleret 10-fods container";
            }
        } else if ($container_type == "20 feet") {
            $title = "20-fods container";
            $desc = $title;
            if ($isolated_container) {
                $title = "20-fods container (Isoleret)";
                $desc = "Isoleret 20-fods container";
            }
        } else if ($container_type == "20 feet high cube") {
            $title = "20-fods HC container";
            $desc = "Ekstra høj 20-fods container";
            if ($isolated_container) {
                $title = "20-fods HC container (Isoleret)";
                $desc = "Ekstra høj isoleret 20-fods container";
            }
        } else if ($container_type == "40 feet") {
            $title = "40-fods container";
            $desc = $title;
            if ($isolated_container) {
                $title = "40-fods container (Isoleret)";
                $desc = "Isoleret 40-fods container";
            }
        } else if ($container_type == "40 feet high cube") {
            $title = "40-fods HC container";
            $desc = "Ekstra høj 40-fods container";
            if ($isolated_container) {
                $title .= "40-fods HC container (Isoleret)";
                $desc = "Ekstra høj isoleret 40-fods container";
            }
        } else if ($container_type == "other") {
            $title = "Container";
            $desc = "Container";
            if ($m2 && $m3) {
                $title = number_format($m3, 1, ',', '') .  ' m³ / ' . number_format($m2, 1, ',', '') . ' m²'  . ' container';
            } else if ($m3) {
                $output .= number_format($m3, 1, ',', '') . 'm³ container';
            } else if ($m2) {
                $output .= number_format($m2, 1, ',', '') . ' m² container';
            }
            if ($isolated_container) {
                $title .= " (Isoleret)";
                $desc = "Isoleret 40-fods container";
            }
        } else {
            $title = "Container";
            $desc = "Container";
            if ($isolated_container) {
                $title = "Container (Isoleret)";
                $desc = "Isoleret container";
            }
        }
        $output .= '<span class="big truncate-text">' . $title . '</span>';
        $output .= '<div class="break"></div>';
        if ($m2 && $m3) {
            $output .= '<span class="detaileddesc truncate-text">' . $desc . ' på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
        } else if ($m2) {
            $output .= '<span class="detaileddesc truncate-text">' . $desc . ' på ' . number_format($m2, 1, ',', '') . ' m²</span>';
        } else if ($m3) {
            $output .= '<span class="detaileddesc truncate-text"' . $desc . ' på ' . number_format($m3, 1, ',', '') . 'm³</span>';
        }
    } else if ($unit_type == "unit_in_container") {
        if ($m2 && $m3) {
            $output .= '<span class="smallbold truncate-text">Depotrum</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="bigbold truncate-text">' . number_format($m2, 1, ',', '') . ' m²</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc truncate-text">Depotrum i container på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
        } else if ($m2) {
            $output .= '<span class="smallbold truncate-text">Depotrum</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="bigbold truncate-text">' . number_format($m2, 1, ',', '') . ' m²</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc truncate-text">Depotrum i container på ' . number_format($m2, 1, ',', '') . ' m²</span>';
        } else if ($m3) {
            $output .= '<span class="smallbold truncate-text">Depotrum</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="bigbold truncate-text">' . number_format($m3, 1, ',', '') . ' m³</span>';
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc truncate-text">Depotrum i container på ' . number_format($m3, 1, ',', '') . ' m³</span>';
        }
    } else if ($unit_type == "indoor") {
        if ($m2 && $m3) {
            $output .= generate_unit_size_smallbold_text($m2, $m3, $partner);
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="bigbold truncate-text">' . number_format($m2, 1, ',', '') . ' m²</span>';
            } else {
                $output .= '<span class="normal truncate-text">' . number_format($m2, 1, ',', '') . ' m²</span>';
            }
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="detaileddesc truncate-text">Indendørs depotrum på ' . number_format($m2, 1, ',', '') . ' m² / ' . number_format($m3, 1, ',', '') . ' m³</span>';
            }
        } else if ($m2) {
            $output .= generate_unit_size_smallbold_text($m2, $m3, $partner);
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="bigbold truncate-text">' . number_format($m2, 1, ',', '') . ' m²</span>';
            } else {
                $output .= '<span class="big truncate-text">' . number_format($m2, 1, ',', '') . ' m²</span>';
            }
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="detaileddesc truncate-text">Indendørs depotrum på ' . number_format($m2, 1, ',', '') . ' m²</span>';
            }
        } else if ($m3) {
            $output .= generate_unit_size_smallbold_text($m2, $m3, $partner);
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="bigbold truncate-text">' . number_format($m3, 1, ',', '') . ' m³</span>';
            } else {
                $output .= '<span class="big truncate-text">' . number_format($m3, 1, ',', '') . ' m³</span>';
            }
            $output .= '<div class="break"></div>';
            if ($partner) {
                $output .= '<span class="detaileddesc truncate-text">Indendørs depotrum på ' . number_format($m3, 1, ',', '') . ' m³</span>';
            }
        }
    } else if ($unit_type == "classic_storage") {
        $output .= '<span class="smallbold truncate-text">Opmagasinering af indbo</span>';
        $output .= '<div class="break"></div>';
        if ($partner) {
            $output .= '<span class="big truncate-text" style="">Møbelopbevaring</span>';
        } else {
            $output .= '<span class="big truncate-text">Opmagasinering</span>';
        }
        if ($partner) {
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc truncate-text">Opbevaring på opvarmet lagerhotel</span>';
        }
        $output .= '<div class="break"></div>';
    } else if ($unit_type == "big_box") {
        $output .= '<span class="smallbold truncate-text">Opmagasinering i big box</span>';
        $output .= '<div class="break"></div>';
        if ($partner) {
            $output .= '<span class="big truncate-text" style="">Big Box på 6 m³</span>';
        } else {
            $output .= '<span class="big truncate-text">Opmagasinering i lukket trækasse på 6 m³</span>';
        }
        if ($partner) {
            $output .= '<div class="break"></div>';
            $output .= '<span class="detaileddesc truncate-text truncate-text">Opbevaring i 6m³</span>';
        }
        $output .= '<div class="break"></div>';
    }

    $output .= '</div>';

    return $output;
}

function generate_unit_size_smallbold_text($m2, $m3, $partner)
{
    if ($partner != 1) {
        return '';
    }
    if ($m2) {
        $m2 = floatval($m2); // Convert m2 to integer if it's a string
        if ($m2 <= 1) {
            $output = '<span class="smallbold">Mikro depotrum</span>';
        } elseif ($m2 > 1 && $m2 <= 2.5) {
            $output = '<span class="smallbold">Mini depotrum</span>';
        } elseif ($m2 > 2.5 && $m2 <= 4) {
            $output = '<span class="smallbold">Lille depotrum</span>';
        } elseif ($m2 > 4 && $m2 <= 6) {
            $output = '<span class="smallbold">Mellem depotrum</span>';
        } elseif ($m2 > 6 && $m2 <= 8) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m2 > 8 && $m2 <= 12) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m2 > 12 && $m2 <= 30) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m2 > 30) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        }
    } else if ($m3) {
        $m3 = floatval($m3); // Convert m3 to integer if it's a string
        if ($m3 <= 2.5) {
            $output = '<span class="smallbold">Mikro depotrum</span>';
        } elseif ($m3 > 2.5 && $m3 <= 6.25) {
            $output = '<span class="smallbold">Mini depotrum</span>';
        } elseif ($m3 > 6.25 && $m3 <= 10) {
            $output = '<span class="smallbold">Lille depotrum</span>';
        } elseif ($m3 > 10 && $m3 <= 15) {
            $output = '<span class="smallbold">Mellem depotrum</span>';
        } elseif ($m3 > 15 && $m3 <= 20) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m3 > 20 && $m3 <= 30) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m3 > 30 && $m3 <= 75) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        } elseif ($m3 > 75) {
            $output = '<span class="smallbold">Stort depotrum</span>';
        }
    } else {
        $output = '<span class="smallbold">Depotrum</span>';
    }

    return $output;
}

function generate_unit_illustration_column($relTypeId, $unit_type, $m2, $m3, $container_type, $isolated_container, $partner)
{
    if ($partner != 1) {
        return '';
    }
    if ($unit_type == "container") {
        if ($container_type == "8 feet") {
            $image_url = plugins_url('size-illustrations/10 feet.png', __FILE__);
            if ($isolated_container) {
                $image_url = plugins_url('size-illustrations/10 feet isolated.png', __FILE__);
            }
        } else if ($container_type == "10 feet") {
            $image_url = plugins_url('size-illustrations/10 feet.png', __FILE__);
            if ($isolated_container) {
                $image_url = plugins_url('size-illustrations/10 feet isolated.png', __FILE__);
            }
        } else if ($container_type == "20 feet") {
            $image_url = plugins_url('size-illustrations/20 feet.png', __FILE__);
            if ($isolated_container) {
                $image_url = plugins_url('size-illustrations/20 feet isolated.png', __FILE__);
            }
        } else if ($container_type == "20 feet high cube") {
            $image_url = plugins_url('size-illustrations/20 feet.png', __FILE__);
            if ($isolated_container) {
                $image_url = plugins_url('size-illustrations/20 feet isolated.png', __FILE__);
            }
        } else if ($container_type == "40 feet") {
            $image_url = plugins_url('size-illustrations/40 feet.png', __FILE__);
            if ($isolated_container) {
                $image_url = plugins_url('size-illustrations/40 feet isolated.png', __FILE__);
            }
        } else if ($container_type == "40 feet high cube") {
            $image_url = plugins_url('size-illustrations/40 feet.png', __FILE__);
            if ($isolated_container) {
                $image_url = plugins_url('size-illustrations/40 feet isolated.png', __FILE__);
            }
        } else {
            $image_url = plugins_url('size-illustrations/20 feet.png', __FILE__);
        }
    } else if ($unit_type == "indoor") {
        if ($m2) {
            $m2 = floatval($m2); // Convert m2 to integer if it's a string
            if ($m2 <= 1) {
                $image_url = plugins_url('size-illustrations/micro.png', __FILE__);
            } elseif ($m2 > 1 && $m2 <= 2.5) {
                $image_url = plugins_url('size-illustrations/mini.png', __FILE__);
            } elseif ($m2 > 2.5 && $m2 <= 4) {
                $image_url = plugins_url('size-illustrations/small.png', __FILE__);
            } elseif ($m2 > 4 && $m2 <= 6) {
                $image_url = plugins_url('size-illustrations/mediums.png', __FILE__);
            } elseif ($m2 > 6 && $m2 <= 8) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m2 > 8 && $m2 <= 12) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m2 > 12 && $m2 <= 30) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m2 > 30) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            }
        } else if ($m3) {
            $m3 = floatval($m3); // Convert m3 to integer if it's a string
            if ($m3 <= 2.5) {
                $image_url = plugins_url('size-illustrations/micro.png', __FILE__);
            } elseif ($m3 > 2.5 && $m3 <= 6.25) {
                $image_url = plugins_url('size-illustrations/mini.png', __FILE__);
            } elseif ($m3 > 6.25 && $m3 <= 10) {
                $image_url = plugins_url('size-illustrations/small.png', __FILE__);
            } elseif ($m3 > 10 && $m3 <= 15) {
                $image_url = plugins_url('size-illustrations/mediums.png', __FILE__);
            } elseif ($m3 > 15 && $m3 <= 20) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m3 > 20 && $m3 <= 30) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m3 > 30 && $m3 <= 75) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            } elseif ($m3 > 75) {
                $image_url = plugins_url('size-illustrations/large.png', __FILE__);
            }
        } else {
            $image_url = plugins_url('size-illustrations/large.png', __FILE__);
        }
    } else if ($unit_type == "classic_storage") {
        $image_url = plugins_url('size-illustrations/classic-storage.png', __FILE__);
    } else if ($unit_type == "big_box") {
        $image_url = plugins_url('size-illustrations/big-box.png', __FILE__);
    } else {
        $image_url = plugins_url('size-illustrations/small.png', __FILE__);
    }

    $output = '<div class="image-column vertical-center">';
    $output .= '<img src="' . $image_url . '" width="70" height="70" alt="Illustration af depotrum" />';
    $output .= '</div>';

    return $output;
}


function generate_non_partner_text($finalOutput)
{
    $current_pod = pods();

    $statistics_data_fields = get_statistics_data_for_single_gd_place($current_pod->field("id"));
    if (empty($statistics_data_fields)) {
        trigger_error("error in generate_non_partner_text: statistics_data_fields is empty for id: " . $current_pod->field("post_title"), E_USER_WARNING);
    }
    $finalOutput .= '<p class="unit_list_non_partner_text"><small>[location] tilbyder depotrum';

    if (($statistics_data_fields['smallest m2 size'] != null) && ($statistics_data_fields['largest m2 size'] != null)) {
        $finalOutput .= ' fra [smallest m2 size] m² til [largest m2 size] m²';
    }
    if (($statistics_data_fields['lowest price'] != null) && ($statistics_data_fields['highest price'] != null)) {
        $finalOutput .= ' i prislejet [lowest price] kr til [highest price] kr';
    }
    $finalOutput .= '. </small></p>';

    $gd_place_title = ($current_pod->field("post_title"));

    $finalOutput = str_replace("[location]", $gd_place_title, $finalOutput);

    foreach ($statistics_data_fields as $field => $value) {
        if (!empty($value)) {
            $rounded = floatval(round($value, 2));
            $numberformat = number_format($value, 0, ',', '.');
            $finalOutput = str_replace("[$field]", $numberformat, $finalOutput);
        }
    }
    return $finalOutput;
}

function sort_depotrum_by_price($available_unit_items)
{
    // Get all depotrum and sort by price
    $AllDepotrumArray = [];

    foreach ($available_unit_items as $depotrum) {

        $id = $depotrum;
        $arrayObject = (object) [
            'id' => $id,
            'price' => get_post_meta($id, 'price', true),
        ];

        if ($arrayObject->price == null) {
            // trigger_error("Encountered depotrum with no price, sorting by m2 size instead. depotrum ID:" .  $id, E_USER_NOTICE);
            throw new Exception('Encountered depotrum with no price, sorting by m2 size instead');
        }

        array_push($AllDepotrumArray, $arrayObject);
    }

    usort($AllDepotrumArray, function ($a, $b) {
        return $a->price > $b->price ? 1 : -1;
    });

    return $AllDepotrumArray;
}

function sort_depotrum_by_m2_size($available_unit_items)
{
    $AllDepotrumArray = [];

    foreach ($available_unit_items as $depotrum) {

        $id = $depotrum;
        $relTypeId = getRelTypeId_unitlist($id);
        $arrayObject = (object) [
            'id' => $id,
            'm2size' => get_post_meta($relTypeId, 'm2', true)
        ];

        if ($arrayObject->m2size == null) {
            // trigger_error("Encountered depotrum with no price or m2 size, sorting by m3 size instead. depotrum ID:" .  $id, E_USER_NOTICE);
            throw new Exception('Encountered depotrum with no price or m2 size, sorting by m3 size instead');
        }
        array_push($AllDepotrumArray, $arrayObject);
    }

    usort($AllDepotrumArray, function ($a, $b) {
        return $a->m2size > $b->m2size ? 1 : -1;
    });

    return $AllDepotrumArray;
}

function sort_depotrum_by_m3_size($available_unit_items)
{
    $AllDepotrumArray = [];

    foreach ($available_unit_items as $id) {

        $relTypeId = getRelTypeId_unitlist($id);
        $arrayObject = (object) [
            'id' => $id,
            'm3size' => get_post_meta($relTypeId, 'm3', true)
        ];

        if ($arrayObject->m3size == null) {
            trigger_error("Encountered depotrum with no price, m2 size or m3 size, giving up on sorting. depotrum ID:" .  $id, E_USER_WARNING);
        }

        array_push($AllDepotrumArray, $arrayObject);
    }

    usort($AllDepotrumArray, function ($a, $b) {
        return $a->m3size > $b->m3size ? 1 : -1;
    });

    return $AllDepotrumArray;
}

function extract_evenly_spaced($array, $num_values)
{
    $size = count($array);
    if ($num_values > $size) {
        return $array;
    }

    $step = $size / $num_values;
    $result = [];

    for ($i = 0; $i < $num_values; $i++) {
        $index = min(intval($i * $step), $size - 1);
        $result[] = $array[$index];
    }

    return $result;
}

function getRelTypeId_unitlist($id)
{
    $relType = get_post_meta($id, 'rel_type', true);
    if (is_array($relType)) {
        // trigger_error("Rel type is an array for depotrum with id: " . $id);
        return $relType['ID'];
    } else {
        return $relType;
    }
}


function generate_booking_form($unit_id, $available_date, $booking_link, $enable_direct_booking)
{
    $nonce = wp_create_nonce('booking_form_nonce_action');

    $form = '
    <div class="formdiv" id="formdiv-' . $unit_id . '" style="max-height: 0px;">

    <div class="lock-in-rate">
    <img src="' . esc_url(plugins_url('img/clock.svg', __FILE__)) . '" alt="Clock Icon" class="clock-icon" />
    Reserver nu til denne pris. Ingen binding.
  </div>


    <form method="post" id="booking_form-' . $unit_id . '" class="booking_form">
    <input type="hidden" id="nonce" name="nonce" value="' . $nonce . '">
    <input type="text" class="move_in_date" id="move_in_date-' . $unit_id . '" name="move_in_date" readonly style="display: none;">
    <input type="number" id="unit_id" name="unit_id" value="' . $unit_id . '" readonly style="display: none;">
    <input type="hidden" id="enable_direct_booking" name="enable_direct_booking" value="' . $enable_direct_booking . '">
    <input type="hidden" id="booking_link" name="booking_link" value="' . $booking_link . '">
    <input type="hidden" id="vibecheck" name="vibecheck">

    <div class="form-row">
    <input type="text" id="first-name" name="first_name" placeholder="Fornavn"required oninvalid="this.setCustomValidity(\'Indtast venligst dit fornavn\')" oninput="this.setCustomValidity(\'\')" />
    <input type="text" id="last-name" name="last_name" placeholder="Efternavn"required oninvalid="this.setCustomValidity(\'Indtast venligst dit efternavn\')" oninput="this.setCustomValidity(\'\')" />
    </div>
    
    <div class="form-row">
      <input type="email" id="email" name="email" placeholder="Email adresse" required  oninvalid="this.setCustomValidity(\'Indtast venligst din email-adresse\')" oninput="this.setCustomValidity(\'\')">
      <input type="tel" id="phone" name="phone" placeholder="Telefon" required minlength="6" maxlength="14" pattern=".{6,14}"  oninvalid="this.setCustomValidity(\'Indtast venligst dit telefonnummer\')" oninput="this.setCustomValidity(\'\')">
    </div>

    <div class="form-row">

<div class="custom-select-wrapper">
    <div class="custom-select">
        <select class="custom-select__trigger" id="date-dropdown" reqiured>Indflytningsdato
        </select>
        <div class="dates">
            <!-- JavaScript will populate this area with date options -->
        </div>
        <div class="dropdown-icon"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="10" viewBox="0 0 15 10" class="svg chevron-down"><path d="M7.5 10a1.5 1.5 0 0 1-1.14-.52l-6-7a1.5 1.5 0 1 1 2.28-2L7.5 6.2 12.36.52a1.5 1.5 0 1 1 2.28 2l-6 7A1.5 1.5 0 0 1 7.5 10z"></path></svg></div>
    </div>
    <div class="error-message" id="date-error-message">Vælg venligst en indflytningsdato</div>
</div>

</div>

<div class="form-row">
<p class="instruction">
Hvis du er usikker på din indflytningsdato, så vælg en cirkadato. Du binder dig ikke til denne dato.
</p>
</div>

    <div class="form-row center-items">
    <input type="submit" id="reserveBtn-' . $unit_id . '" onclick="gtag_report_conversion();"  value="Reservér">
    </div>

    <p class="instruction full-width">
    Ingen forpligtelse til at leje.
    </p>
    
  </form>
  </div>';
    return $form;
}
