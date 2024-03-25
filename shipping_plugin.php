<?php
/*
Plugin Name: Custom Shipping Calculator
Description: Plugin for calculating shipping costs based on WooCommerce cart contents and user address.
Version: 1.0
Author: Mahir
*/
//Function to geocode address using OpenStreetMap Nominatim API
function geocode_address($address) {
    // Google Maps API Key
    $api_key = 'AIzaSyBm2HUy0IzvT8hK3e2gvSAPXV5dsR0P_gA';
    
    // Construct the request URL
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $api_key;

    // Send the request
    $response = file_get_contents($url);

    // Check if request was successful
    if ($response === false) {
        return false;
    }

    // Decode the JSON response
    $data = json_decode($response, true);

    // Check if response contains any results
    if ($data['status'] === 'OK' && isset($data['results'][0]['geometry']['location'])) {
        $location = $data['results'][0]['geometry']['location'];
        return array("latitude" => $location['lat'], "longitude" => $location['lng']);
    } else {
        return false;
    }
}


//Function to calculate distance between two coordinates using the Haversine formula
function calculate_distance($coord1, $coord2) {
    // Earth radius in miles
    $radius = 3959; // Earth's radius in miles (approximately)

    // Convert latitude and longitude from degrees to radians
    $lat1 = deg2rad($coord1['latitude']);
    $lon1 = deg2rad($coord1['longitude']);
    $lat2 = deg2rad($coord2['latitude']);
    $lon2 = deg2rad($coord2['longitude']);

    // Haversine formula
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $radius * $c; // Result in miles

    // Round the distance to one decimal place
    $distance_round = round($distance, 1);

    return $distance_round;
}

//Hook into WooCommerce cart to calculate shipping cost dynamically
add_action('woocommerce_cart_calculate_fees', 'calculate_custom_shipping_cost');

function calculate_custom_shipping_cost() 
{
    $total_shipping_cost = 0;
    $zones = array(
        "red" => array(
            "prices" => array("small_truck" => 120, "large_dump_truck" => 150, "flatbed" => 210),
            "max_miles" => 10.4 // Maximum mile delivery for the red zone
        ),
        "blue" => array(
            "prices" => array("small_truck" => 155, "large_dump_truck" => 190, "flatbed" => 240),
            "max_miles" => 15.4
        ),
        "yellow" => array(
            "prices" => array("small_truck" => 185, "large_dump_truck" => 230, "flatbed" => 275),
            "max_miles" => 20.4 
        ),
        "black" => array(
            "prices" => array("small_truck" => 215, "large_dump_truck" => 270, "flatbed" => 310),
            "max_miles" => 25.4
        ),
        "purple" => array(
            "prices" => array("small_truck" => 250, "large_dump_truck" => 310, "flatbed" => 350),
            "max_miles" => 30.4
        ),
        "light_blue" => array(
            "prices" => array("small_truck" => 285, "large_dump_truck" => 375, "flatbed" => 365),
            "max_miles" => 35.4
        ),
        "other" => array(
            "prices" => array("small_truck" => 285, "large_dump_truck" => 375, "flatbed" => 365),
            "max_miles" => 50,
            "additional_charge_per_mile" => 5.80
        )
    );

    //Get cart contents
    $cart_contents = WC()->cart->get_cart();
    //Get user's shipping address from WooCommerce
    $user_address = WC()->customer->get_shipping_address();
    // Check if the user's shipping address is set
    if (!empty($user_address)) {
        // Geocode user's address
        $user_coordinates = geocode_address($user_address);
        // Example warehouse locations (latitude and longitude)
        $warehouse_locations = array(
            array("name" => "Sand Facility North", "latitude" => 35.53636, "longitude" => -97.37676),
        );

        // Calculate total distance from warehouses to the delivery address
        $total_distance = 0;
        foreach ($warehouse_locations as $warehouse) {
            $warehouse_coordinates = array("latitude" => $warehouse['latitude'], "longitude" => $warehouse['longitude']);
            $distance = calculate_distance($warehouse_coordinates, $user_coordinates);
            $total_distance += $distance;// Add distance to total shipping cost
        }
        // Add calculated shipping cost to WooCommerce cart

    } else {
        echo "User's shipping address is not set.";
    }


// Calculate shipping cost for Others Zone
if ($total_distance > $zones["other"]["max_miles"]) {
    $distance_over_max = $total_distance - $zones["other"]["max_miles"];
    $additional_charge = $distance_over_max * $zones["others"]["additional_charge_per_mile"];

    // Add base shipping cost plus additional charge to total shipping cost
    switch ($transport_type) {
        case "small_truck":
            $total_shipping_cost += $zones["others"]["prices"]["small_truck"] + $additional_charge;
            break;
        case "large_dump_truck":
            $total_shipping_cost += $zones["others"]["prices"]["large_dump_truck"] + $additional_charge;
            break;
        case "flatbed":
            $total_shipping_cost += $zones["others"]["prices"]["flatbed"] + $additional_charge;
            break;
    }
}


    $zone = "red"; // Initialize zone variable
    
   foreach ($zones as $zone_name => $zone_data) {
       if ($total_distance <= $zone_data['max_miles']) {
           $zone = $zone_name;
           break; // Exit loop once the appropriate zone is found
       }

       
   }

   if (empty($zone)) {
       echo "No suitable zone found for the delivery address.";
       return; // Exit function if no suitable zone is found
   }
   

    foreach ($cart_contents as $cart_item) {
    $product = $cart_item['data'];
    $quantity = $cart_item['quantity'];
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs')); // Get product categories
    $transport_type = $zone; // Set transport type based on address zone

    // Initialize total weight for each product
    $total_weight_product = $quantity;
    $total_weight_flatbed = 0;

    // Check if the product belongs to 'boulders' category
    if (in_array('boulders', $categories)) {
        $total_weight_flatbed += $quantity;
    }
	
		
            if( $categories === 'soils-sands'){
                while ($total_weight_product > 0) {
                    if ($total_weight_product <= 12) {
                        $total_shipping_cost += $zones[$transport_type]['prices']['small_truck'];
                        break;
                    } else {
                        // Calculate excess weight
                        $excess_weight = $total_weight_product - 20;
    					if ($excess_weight < 0) {
						 $excess_weight = abs($excess_weight);
						}		
                        // Calculate number of large dump trucks required
                        $total_car = ceil($excess_weight / 20);
    
                        $total_shipping_cost += $zones[$transport_type]['prices']['large_dump_truck'] * $total_car;
                        $total_weight_product -= ($total_car * 20);
                        break;

                    }
                }
            }else if($categories === 'boulders')
               {
                while ($total_weight_flatbad > 0) {
                    if ($total_weight_flatbad <= 22) {
                        $total_shipping_cost += $zones[$transport_type]['prices']['flatbed'];
                        break;
                    } else {
                        // Calculate excess weight
                        $excess_weight = $total_weight_product - 20;
    					if ($excess_weight < 0) {
						 $excess_weight = abs($excess_weight);
						}		
                        // Calculate number of large dump trucks required
                        $total_car = ceil($excess_weight / 20);
    
                        $total_shipping_cost += $zones[$transport_type]['prices']['flatbed'] * $total_car;
                        $total_weight_product -= ($total_car * 20);
                        break;
                    }
                }
            }
            elseif( $categories === 'compost' || $categories === 'mulch')
            {
                while ($total_weight_product > 0) {
                    if ($total_weight_product <= 14) {
                        $total_shipping_cost += $zones[$transport_type]['prices']['small_truck'];
                        break;
                    } else {
                        // Calculate excess weight
                          $excess_weight = $total_weight_product - 25;
    					if ($excess_weight < 0) {
						 $excess_weight = abs($excess_weight);
						}		
                        // Calculate number of large dump trucks required
                        $total_car = ceil($excess_weight / 25);
    
                        $total_shipping_cost += $zones[$transport_type]['prices']['large_dump_truck'] * $total_car;
                        $total_weight_product -= ($total_car * 25);
                        break;
                    }
                }
                
            }
        }
        
    
    
           
        WC()->cart->add_fee('Shipping', $total_shipping_cost);
            
    }
    // Add calculated shipping cost to WooCommerce cart
    

    

	


?>