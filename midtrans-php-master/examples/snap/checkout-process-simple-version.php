<?php
// Import namespace Midtrans
namespace Midtrans;

require_once dirname(__FILE__) . '/../../Midtrans.php';

// Set Server Key and Client Key from Midtrans
// Server Key should be kept secret and never exposed on client side
Config::$serverKey = 'SB-Mid-server-7NcW4z8ydCiA_WHwkgcRzq5S'; // Ensure this is the correct key for Sandbox or Production
Config::$isProduction = false; // Change to true for production environment
Config::$clientKey = 'SB-Mid-client-Ic4AOrBpiCtethWu'; // Make sure this is correct for the environment you're using

// Optional: Enable debugging mode and 3DS (for credit card transactions)
Config::$isSanitized = Config::$is3ds = true;

// Define billing and shipping address (optional)
$billing_address = array(
    'first_name' => "Andri",
    'last_name' => "Litani",
    'address' => "Jalan Mawar",
    'city' => "Jakarta",
    'postal_code' => "12345",
    'phone' => "081122334455",
    'country_code' => "IDN"
);

$shipping_address = array(
    'first_name' => "Andri",
    'last_name' => "Litani",
    'address' => "Jalan Melati",
    'city' => "Jakarta",
    'postal_code' => "54321",
    'phone' => "081122334455",
    'country_code' => "IDN"
);

// Required transaction details
$transaction_details = array(
    'order_id' => rand(),
    'gross_amount' => 94000, // no decimal allowed for credit card
);

// Optional item details
$item_details = array(
    array(
        'id' => 'a1',
        'price' => 94000,
        'quantity' => 1,
        'name' => "Apple"
    ),
);

// Optional customer details
$customer_details = array(
    'first_name' => "Andri",
    'last_name' => "Litani",
    'email' => "andri@litani.com",
    'phone' => "081122334455",
    'billing_address' => $billing_address,
    'shipping_address' => $shipping_address
);

// Fill transaction details
$transaction = array(
    'transaction_details' => $transaction_details,
    'customer_details' => $customer_details,
    'item_details' => $item_details,
);

$snap_token = '';
try {
    $snap_token = Snap::getSnapToken($transaction);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
    die();
}

echo "snapToken = ".$snap_token;

// Optional warning message (for development)
function printExampleWarningMessage() {
    if (strpos(Config::$serverKey, 'your ') !== false) {
        echo "<code>";
        echo "<h4>Please set your server key from sandbox</h4>";
        echo "In file: " . __FILE__;
        echo "<br>";
        echo "<br>";
        echo htmlspecialchars('Config::$serverKey = \'<your server key>\';');
        die();
    }
}

printExampleWarningMessage();
?>

<!DOCTYPE html>
<html>
    <body>
        <button id="pay-button">Pay!</button>

        <!-- Script untuk Snap Midtrans -->
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-Ic4AOrBpiCtethWu"></script>

        <script type="text/javascript">
            var payButton = document.getElementById('pay-button');
            payButton.addEventListener('click', function () {
                snap.pay('<?php echo $snap_token; ?>', {
                    onSuccess: function(result){
                        console.log(result);
                        alert("Payment successful!");
                    },
                    onPending: function(result){
                        console.log(result);
                        alert("Payment is pending.");
                    },
                    onError: function(result){
                        console.log(result);
                        alert("Payment failed.");
                    },
                    onClose: function(){
                        alert("You closed the popup without finishing the payment");
                    }
                });
            });
        </script>
    </body>
</html>
