<?php
/*
   Include this file's code wherever a protected resource exists,
   and call the main function:
     zeroclick($resource_id, $resource_cost, $paid_uri)
   which returns a boolean stating whether the user has paid for the
   resource during their session.

   Example:
    <?php
    include 'zeroclick.php';
    $is_paid = zeroclick(123, 0.02, 'http://www.satoshinews.com/article/123');
    if ($is_paid) {
      // show article
    }
    ?>
*/

// Set this string to your merchant address for receiving payments
$btc_address = '';

// Main function: returns True if resource cost has been paid, False otherwise
// $resource_id = Unique identifier for resource
// $resource_cost = Amount that must be paid before resource is available
// $paid_uri = URI for client to redirect after paying
function zeroclick($resource_id, $resource_cost, $paid_uri) {
  session_start();
  if (isset($_SESSION[$resource_id])) { // resource is being re-requested
    return zeroclick_callback($resource_id, $resource_cost, $paid_uri);
  } else {
    return zeroclick_create($resource_id, $resource_cost, $paid_uri);
  }
}

function zeroclick_create($resource_id, $resource_cost, $paid_uri) {
  // Create payment address and session, send 402 back to client
  $api_root = 'https://blockchain.info/api/receive';
  $params = 'method=create&address=' . $btc_address;
  $response = file_get_contents($api_root . '?' . $params);
  $blockchain_obj = json_decode($response);
  $payment_addr = $blockchain_obj->input_address;
  session_start();
  $_SESSION[$resource_id] = array(
    'payment_addr' => $payment_addr,
    'resource_cost' => $resource_cost,
    'paid_uri' = $paid_uri
  );
  $header = 'HTTP/1.1 402 Payment Required\r\n';
  $header .= 'Payment-URI: bitcoin:' . $payment_addr . '?amount=' . $resource_cost . '\r\n';
  $header .= 'Paid-URI: ' . $paid_uri . '\r\n';
  header($header);
  return False;
}

function zeroclick_callback($resource_id, $resource_cost, $paid_uri) {
  // Check payment address for sufficient balance
  $session_resource = $_SESSION[$resource_id];
  if (isset($session_resource['payment_addr'])) {
    $payment_addr = $session_resource['payment_addr'];
    $api_call = 'https://blockchain.info/rawaddr/' . $payment_addr . '?limit=0';
    $response = file_get_contents($api_call);
    $blockchain_obj = json_decode($response);
    $balance = $blockchain_obj->final_balance;
    return ($balance >= $resource_cost);
  } else {
    return zeroclick_create($resource_id, $resource_cost, $paid_uri);
  }
}
?>

