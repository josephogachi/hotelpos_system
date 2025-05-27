<?php
session_start();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, #f9d423 0%, #e14fad 50%, #00b09b 100%);
        }
        
        .container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            margin: auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            height: 80vh;
        }
        
        /* Left Section - Image Slider */
        .left-section {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        
        .slider-container {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .slider {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .slider img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }
        
        .slider img.active {
            opacity: 1;
        }
        
        /* Right Section - Login */
        .right-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            justify-content: center;
            align-items: center;
        }
        
        .logo-container {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .logo-container img {
            max-width: 120px;
            margin-bottom: 1rem;
        }
        
        .hotel-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e53935;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .login-container {
            width: 100%;
            max-width: 350px;
        }
        
        .pin-display {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .pin-dot {
            width: 20px;
            height: 20px;
            margin: 0 8px;
            border-radius: 50%;
            background: #ddd;
            transition: all 0.3s ease;
        }
        
        .pin-dot.filled {
            background: linear-gradient(45deg, #e53935, #ff8f00);
            transform: scale(1.2);
        }
        
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 1.5rem;
        }
        
        .key {
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            border: none;
            border-radius: 12px;
            height: 60px;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .key:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .key:active {
            transform: translateY(1px);
        }
        
        .key.clear {
            grid-column: span 2;
            background: linear-gradient(145deg, #ff5252, #d50000);
            color: white;
        }
        
        .key.submit {
            background: linear-gradient(145deg, #4caf50, #2e7d32);
            color: white;
        }
        
        .error-message {
            color: #d32f2f;
            text-align: center;
            margin-top: 1rem;
            font-weight: 500;
            min-height: 1.5rem;
        }
        
        .hidden-input {
            position: absolute;
            opacity: 0;
            height: 0;
            width: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Section - Image Slider -->
        <div class="left-section">
            <div class="slider-container">
                <div class="slider">
                    <?php for ($i = 1; $i <= 15; $i++): ?>
                        <img src="assets/images/hotel<?= $i ?>.jpeg" alt="Hotel <?= $i ?>" <?= $i === 1 ? 'class="active"' : '' ?>>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Login -->
        <div class="right-section">
            <div class="logo-container">
                <img src="assets/images/logo.jfif" alt="Hotel Logo">
                <div class="hotel-name">GRAND PLAZA HOTEL</div>
            </div>
            
            <div class="login-container">
                <form id="loginForm" method="POST" action="auth/login.php">
                    <!-- Hidden input for actual PIN submission -->
                    <input type="password" name="pin" id="pinInput" maxlength="6" class="hidden-input" required>
                    
                    <!-- Visual PIN display -->
                    <div class="pin-display">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="pin-dot" id="dot<?= $i ?>"></div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Keypad -->
                    <div class="keypad">
                        <?php for ($i = 1; $i <= 9; $i++): ?>
                            <button type="button" class="key" onclick="appendDigit(<?= $i ?>)"><?= $i ?></button>
                        <?php endfor; ?>
                        <button type="button" class="key clear" onclick="clearPin()">Clear</button>
                        <button type="button" class="key" onclick="appendDigit(0)">0</button>
                        <button type="button" class="key submit" onclick="submitPin()">Login</button>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let currentPin = '';
        const maxLength = 6;
        
        function appendDigit(digit) {
            if (currentPin.length < maxLength) {
                currentPin += digit;
                updatePinDisplay();
            }
        }
        
        function clearPin() {
            currentPin = '';
            updatePinDisplay();
        }
        
        function updatePinDisplay() {
            // Update dots
            for (let i = 0; i < maxLength; i++) {
                const dot = document.getElementById(`dot${i}`);
                if (i < currentPin.length) {
                    dot.classList.add('filled');
                } else {
                    dot.classList.remove('filled');
                }
            }
            
            // Update hidden input
            document.getElementById('pinInput').value = currentPin;
        }
        
        function submitPin() {
            if (currentPin.length === maxLength) {
                document.getElementById('loginForm').submit();
            } else {
                document.querySelector('.error-message').textContent = 'Please enter a 6-digit PIN';
            }
        }
        
        // Image slider functionality
        function rotateImages() {
            const images = document.querySelectorAll('.slider img');
            let currentIndex = 0;
            
            // Find current active image
            images.forEach((img, index) => {
                if (img.classList.contains('active')) {
                    currentIndex = index;
                    img.classList.remove('active');
                }
            });
            
            // Calculate next index
            currentIndex = (currentIndex + 1) % images.length;
            images[currentIndex].classList.add('active');
        }
        
        // Set interval for image rotation (every 3 seconds)
        setInterval(rotateImages, 3000);
        
        // Clear error message when typing starts
        document.addEventListener('DOMContentLoaded', () => {
            const keys = document.querySelectorAll('.key:not(.clear):not(.submit)');
            keys.forEach(key => {
                key.addEventListener('click', () => {
                    const errorMsg = document.querySelector('.error-message');
                    if (errorMsg) errorMsg.textContent = '';
                });
            });
        });
    </script>
</body>
</html>