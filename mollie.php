<?php

require_once __DIR__ . "/vendor/autoload.php";
include('config.php');

$mollie = new \Mollie\Api\MollieApiClient();
if ($mollie_test || isset($_POST["test"])) {
    $mollie->setApiKey($mollie_apikey_test);
} else {
    $mollie->setApiKey($mollie_apikey_live);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["id"])) {
	// client is revbank plugin

        $id = $_POST["id"];
        if (! preg_match("/^tr_\\w+\\z/", $id)) die("Nope");
        header("Content-Type: application/json; charset=US-ASCII");

        try {
            $payment = $mollie->payments->get($id);
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            print json_encode(["ok" => false, "message" => (
                $e->getCode() == 404 ? "not found" : "API communication error"
            )]);
            exit();
        }

        if (! $payment->isPaid()) {
            print json_encode(["ok" => false, "message" => "payment " . $payment->status]);
            exit();
        }
        if (! $payment->metadata->revbank_status) {
            print json_encode(["ok" => false, "message" => "not a RevBank transaction"]);
            exit();
        }
        if ($payment->amount->currency != "EUR") {
            print json_encode(["ok" => false, "status" => "unknown currency (shouldn't happen)"]);
            exit();
        }
        if (isset($_POST["action"])) {
            if ($_POST["action"] == "abort") {
                if ($payment->metadata->revbank_status != "pending") {
                    print json_encode(["ok" => false, "message" => "can't cancel non-pending"]);
                    exit();
                }
                $payment->metadata = ["revbank_status" => "unspent"];
                $payment->update();
                print json_encode(["ok" => true]);
                exit();
            }
            if ($_POST["action"] == "finalize") {
                $payment->metadata = ["revbank_status" => "spent"];
                $payment->update();
                print json_encode(["ok" => true]);
                exit();
            }
            die("Unsupported action.");
        }

        if ($payment->metadata->revbank_status != "unspent") {
            print json_encode(["ok" => false, "message" => "already spent"]);
            exit();
        }
        $payment->metadata = ["revbank_status" => "pending"];
        $payment->update();
        
        $amount = $payment->amount->value;
        if ($amount < 0) die("Negative?!");

        if ($payment->mode == "test") {
            print json_encode(["ok" => true, "amount" => "0.00", "test_amount" => $amount]);
        } else {
            print json_encode(["ok" => true, "amount" => $amount]);
        }
        exit();
    } else {
	// client is user

        $amount = $_POST["amount"];
        if (! preg_match("/^[0-9]+(?:[,.][0-9]{2})?\\z/", $amount)) die("Invalid amount");
        $amount = preg_replace("/,/", ".", $amount);
        if (! preg_match("/\\./", $amount)) $amount .= ".00";
    
        if ($amount < 13.37) die("Minimum 13.37");
        if ($amount > 150) die("Maximum 150.00");
    
        $payment = $mollie->payments->create([
            "amount" => [ "value" => $amount, "currency" => "EUR" ],
            "description" => "RevBank deposit",
            "redirectUrl" => "https://deposit.revspace.nl/?id=",
            "metadata" => [ "revbank_status" => "unspent" ],
        ]);
        $payment->redirectUrl .= $payment->id;
        $payment->update();
        header("Location: " . $payment->getCheckoutUrl(), true, 303);
        exit();
    }
}

?>
