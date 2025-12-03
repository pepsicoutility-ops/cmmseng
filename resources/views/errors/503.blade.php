<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Maintenance - PEP CMMS Engineering</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('/images/pepsico-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .maintenance-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
        }

        .logo {
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease-out;
        }

        .logo svg {
            width: 120px;
            height: 120px;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
        }

        .content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.8s ease-out;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .subtitle {
            font-size: 1.25rem;
            color: #e0e0e0;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .message {
            font-size: 1rem;
            color: #d0d0d0;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .icon {
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        .icon svg {
            width: 80px;
            height: 80px;
            fill: #fff;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
        }

        .info-box {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid #3b82f6;
        }

        .info-box p {
            font-size: 0.95rem;
            color: #e8e8e8;
            margin: 0.5rem 0;
        }

        .info-box strong {
            color: #fff;
            font-weight: 600;
        }

        .footer {
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #b0b0b0;
            animation: fadeIn 1s ease-out 0.5s both;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        @media (max-width: 640px) {
            h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
            
            .content {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="logo">
            <!-- PepsiCo Logo -->
            <img src="/images/pepsico-logo.jpeg" alt="PepsiCo Logo" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);">
        </div>

        <div class="content">
            <div class="icon">
                <!-- Tools/Maintenance Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21.71 8.71l-4.42-4.42c-.39-.39-1.02-.39-1.41 0l-1.29 1.29 5.83 5.83 1.29-1.29c.39-.39.39-1.02 0-1.41zM3 17.25V21h3.75L17.81 9.94l-5.83-5.83L3 17.25z"/>
                </svg>
            </div>

            <h1>System Maintenance</h1>
            <p class="subtitle">We'll be back shortly</p>
            <p class="message">
    Our CMMS Engineering system is currently undergoing scheduled maintenance to improve your experience.
</p>


            <div class="info-box">
                <p><strong>What's happening?</strong></p>
                <p>We're performing system updates and improvements to ensure optimal performance.</p>
                <p style="margin-top: 1rem;"><strong>Expected Duration:</strong> Approximately 15-30 minutes</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} PepsiCo CMMS Engineering. All rights reserved.</p>
            <p style="margin-top: 0.5rem; font-size: 0.8rem;">For urgent support, please contact your system administrator</p>
        </div>
    </div>
</body>
</html>
