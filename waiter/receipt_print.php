<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/../includes/db.php';

/**
 * Generates an 8-digit unique receipt code
 */
function generateReceiptCode($pdo) {
    do {
        $code = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM receipts WHERE unique_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn() > 0);
    return $code;
}

/**
 * Generates ESC/POS compatible receipt for EPSON TM-T20 with optimized logo
 */
function generateEscposReceipt($data, $logoPath = null) {
    // ESC/POS commands
    $esc = chr(27);
    $init = $esc."@";
    $center = $esc."a".chr(1);
    $left = $esc."a".chr(0);
    $boldOn = $esc."E".chr(1);
    $boldOff = $esc."E".chr(0);
    $doubleHeight = $esc."d".chr(1);
    $normalHeight = $esc."d".chr(0);
    $cut = $esc."m";
    $lineBreak = "\n";
    
    // Start building receipt
    $receipt = $init; // Initialize printer
    
    // Compact hotel header
    $receipt .= $center.$boldOn.$doubleHeight;
    $receipt .= "SUNSET HOTEL".$lineBreak;
    $receipt .= $boldOff.$normalHeight;
    $receipt .= "Eastleigh 7th St, Nairobi".$lineBreak;
    $receipt .= "Tel: +254 712 345678".$lineBreak.$lineBreak;
    
    // Order info
    $receipt .= $left;
    $receipt .= "Receipt: ".$data['receipt_number'].$lineBreak;
    $receipt .= "Date: ".date('d/m/Y H:i').$lineBreak;
    $receipt .= "Table: ".$data['table_number'].$lineBreak;
    $receipt .= "Waiter: ".$data['waiter_name'].$lineBreak;
    $receipt .= str_repeat("-", 32).$lineBreak;
    
    // Items with optimized spacing
    $receipt .= $boldOn.str_pad("ITEM", 18).str_pad("QTY", 5).str_pad("AMOUNT", 9).$lineBreak.$boldOff;
    foreach ($data['order_items'] as $item) {
        $name = substr($item['name'], 0, 18);
        $qty = str_pad($item['qty'], 3);
        $amount = str_pad(number_format($item['price']*$item['qty'], 2), 9);
        $receipt .= str_pad($name, 18).$qty.$amount.$lineBreak;
    }
    
    // Footer
    $receipt .= str_repeat("-", 32).$lineBreak;
    $receipt .= $boldOn."TOTAL:".str_repeat(" ", 17)."Ksh ".number_format($data['total_amount'], 2).$lineBreak.$boldOff;
    $receipt .= str_repeat("-", 32).$lineBreak.$lineBreak;
    
    // Tracking code and delivery info
    $receipt .= $center.$boldOn."TRACKING CODE".$lineBreak.$boldOff;
    $receipt .= $center.$data['unique_code'].$lineBreak.$lineBreak;
    $receipt .= $center."For delivery call:".$lineBreak;
    $receipt .= $center.$boldOn."+254 712 345678".$lineBreak.$lineBreak.$boldOff;
    $receipt .= $center."Thank you!".$lineBreak;
    
    // Cut paper (partial cut)
    $receipt .= $lineBreak.$lineBreak.$lineBreak.$cut;
    
    return $receipt;
}

/**
 * Generates HTML preview of receipt with small logo
 */
function generateHtmlReceipt($data) {
    // Pre-calculate all dynamic values
    $formattedTotal = number_format($data['total_amount'], 2);
    $currentDate = date('d/m/Y H:i');
    
    // Build items HTML
    $items = '';
    foreach ($data['order_items'] as $item) {
        $items .= '<div class="item-row">
            <div class="item-name">' . htmlspecialchars($item['name']) . '</div>
            <div class="item-qty">' . $item['qty'] . '</div>
            <div class="item-price">Ksh ' . number_format($item['price'] * $item['qty'], 2) . '</div>
        </div>';
    }

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            width: 80mm; 
            margin: 0 auto; 
            padding: 3mm;
            font-size: 12px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 5px; 
        }
        .logo { 
            width: 50px; 
            height: auto;
            margin: 0 auto 5px;
            display: block;
        }
        .center { 
            text-align: center; 
        }
        .bold { 
            font-weight: bold; 
        }
        .divider { 
            border-top: 1px dashed #000; 
            margin: 5px 0; 
        }
        .tracking-code { 
            background: #f5f5f5; 
            padding: 5px; 
            text-align: center; 
            font-weight: bold;
            font-size: 11px;
            margin: 5px 0;
            border-radius: 3px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .item-name {
            flex: 3;
        }
        .item-qty {
            flex: 1;
            text-align: center;
        }
        .item-price {
            flex: 2;
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            margin-top: 5px;
        }
        .footer {
            font-size: 11px;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/images/logo.jfif" class="logo" alt="Sunset Hotel">
        <div class="bold" style="font-size: 14px;">SUNSET HOTEL</div>
        <div>Eastleigh 7th Street, Nairobi</div>
        <div>Tel: +254 712 345678</div>
    </div>
    
    <div class="divider"></div>
    
    <div>
        <div><span class="bold">Receipt:</span> {$data['receipt_number']}</div>
        <div><span class="bold">Date:</span> {$currentDate}</div>
        <div><span class="bold">Table:</span> {$data['table_number']}</div>
        <div><span class="bold">Waiter:</span> {$data['waiter_name']}</div>
    </div>
    
    <div class="divider"></div>
    
    <div class="bold">ORDER ITEMS</div>
    <div class="item-row bold">
        <div class="item-name">Item</div>
        <div class="item-qty">Qty</div>
        <div class="item-price">Amount</div>
    </div>
    {$items}
    
    <div class="divider"></div>
    
    <div class="item-row total-row">
        <div class="item-name">TOTAL:</div>
        <div class="item-price">Ksh {$formattedTotal}</div>
    </div>
    
    <div class="tracking-code">TRACKING CODE: {$data['unique_code']}</div>
    
    <div class="footer">
        <div>For delivery call: <span class="bold">+254 712 345678</span></div>
        <div style="margin-top: 5px;">Thank you for dining with us!</div>
    </div>
</body>
</html>
HTML;
}

// Main processing
try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    // Validate input
    if (empty($input['order_items']) || !isset($input['total_amount'])) {
        throw new Exception('Missing required order data');
    }

    // Start transaction
    $pdo->beginTransaction();

    // 1. Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, waiter_id, table_number, total, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $input['table_number'] ?? 'N/A',
        $input['total_amount']
    ]);
    $orderId = $pdo->lastInsertId();

    // 2. Add order items
    foreach ($input['order_items'] as $item) {
        if (empty($item['name']) || !isset($item['qty']) || !isset($item['price'])) {
            throw new Exception("Invalid order item format");
        }

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) 
                              VALUES (?, (SELECT id FROM products WHERE name = ? LIMIT 1), ?, ?, ?)");
        $stmt->execute([$orderId, $item['name'], $item['name'], $item['qty'], $item['price']]);
    }

    // 3. Generate receipt
    $receiptNumber = 'RCPT-'.date('Ymd').'-'.str_pad($orderId, 4, '0', STR_PAD_LEFT);
    $uniqueCode = generateReceiptCode($pdo);

    $stmt = $pdo->prepare("INSERT INTO receipts (order_id, receipt_number, unique_code, printed_by, payment_status) VALUES (?, ?, ?, ?, 'unpaid')");
    $stmt->execute([$orderId, $receiptNumber, $uniqueCode, $_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();

    // Generate receipts
    $logoPath = __DIR__.'/../assets/images/logo.jfif';
    $receiptData = [
        'order_items' => $input['order_items'],
        'total_amount' => $input['total_amount'],
        'table_number' => $input['table_number'] ?? 'N/A',
        'waiter_name' => $input['waiter_name'] ?? $_SESSION['user_name'] ?? 'Waiter',
        'unique_code' => $uniqueCode,
        'receipt_number' => $receiptNumber
    ];
    
    $escposReceipt = generateEscposReceipt($receiptData, file_exists($logoPath) ? $logoPath : null);
    $htmlReceipt = generateHtmlReceipt($receiptData);

    // Return response
    echo json_encode([
        'success' => true,
        'receipt_number' => $receiptNumber,
        'unique_code' => $uniqueCode,
        'escpos_data' => base64_encode($escposReceipt),
        'receipt_html' => $htmlReceipt
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing receipt',
        'error' => $e->getMessage()
    ]);
}