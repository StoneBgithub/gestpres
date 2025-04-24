<?php
session_start();
$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSI - Scan de Présence</title>
    <!-- Inclusion de Tailwind CSS -->
    <link href="./assets/css/tailwind.min.css" rel="stylesheet">
    <!-- Polices personnalisées -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bibliothèque de scan QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #038C33 0%, #014023 100%);
        }
        .error-gradient-bg {
            background: linear-gradient(135deg, #dc241f 0%, #a71d1a 100%);
        }
        .custom-btn {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #038C33 0%, #014023 100%);
            color: white;
            position: relative;
            z-index: 10;
        }
        .custom-btn:hover {
            background: #E9F2A0 !important;
            color: #014023 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }
        .btn-arrival {
            background: linear-gradient(135deg, #009543 0%, #006d2c 100%);
        }
        .btn-arrival:hover {
            background: #E9F2A0 !important;
            color: #006d2c !important;
        }
        .btn-departure {
            background: linear-gradient(135deg, #fbde4a 0%, #d4b700 100%);
        }
        .btn-departure:hover {
            background: #E9F2A0 !important;
            color: #d4b700 !important;
        }
        .card-shine {
            position: relative;
            overflow: hidden;
        }
        .card-shine::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: transform 0.5s;
            pointer-events: none;
        }
        .card-shine:hover::after {
            transform: rotate(30deg) translate(0, 50%);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
        }
        #qr-reader img {
            display: none;
        }
        #qr-reader__scan_region {
            position: relative;
        }
        #qr-reader__dashboard_section_swaplink {
            display: none;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-container {
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .modal.active .modal-container {
            transform: translateY(0);
        }
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            width: 100%;
            background-color: #009543;
            transform-origin: left;
        }
        .error-progress-bar {
            background-color: #dc241f;
        }
        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #fbde4a;
            top: 50%;
            animation: scan 2s linear infinite;
        }
        @keyframes scan {
            0% { top: 20%; }
            50% { top: 80%; }
            100% { top: 20%; }
        }
        .scan-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid #009543;
            border-radius: 16px;
            box-shadow: 0 0 0 rgba(0, 149, 67, 0.5);
            animation: pulse 2s infinite;
        }
        .corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border-color: #009543;
            border-width: 4px;
        }
        .corner-top-left {
            top: -2px;
            left: -2px;
            border-top: 4px solid #009543;
            border-left: 4px solid #009543;
            border-bottom: none;
            border-right: none;
        }
        .corner-top-right {
            top: -2px;
            right: -2px;
            border-top: 4px solid #009543;
            border-right: 4px solid #009543;
            border-bottom: none;
            border-left: none;
        }
        .corner-bottom-left {
            bottom: -2px;
            left: -2px;
            border-bottom: 4px solid #009543;
            border-left: 4px solid #009543;
            border-top: none;
            border-right: none;
        }
        .corner-bottom-right {
            bottom: -2px;
            right: -2px;
            border-bottom: 4px solid #009543;
            border-right: 4px solid #009543;
            border-top: none;
            border-left: none;
        }
        .method-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .method-tab {
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin: 0 5px;
            transition: all 0.3s ease;
            background-color: #e5e7eb;
            color: #4b5563;
        }
        .method-tab.active {
            background: linear-gradient(135deg, #038C33 0%, #014023 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .method-tab:hover:not(.active) {
            background-color: #d1d5db;
        }
        .method-container {
            max-width: 600px;
            margin: 0 auto;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .fade-enter {
            opacity: 0;
            transform: translateY(10px);
        }
        .fade-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .shake-animation {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-10px); }
            100% { transform: translateX(0); }
        }
        .footer-bg {
            background-color: #011F12;
        }
        .footer-link {
            color: #F2CE16;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .footer-link:hover {
            color: #FFFFFF;
            text-decoration: underline;
        }
        .footer-divider {
            border-color: #038C33;
            opacity: 0.7;
            height: 2px;
        }
    </style>
</head>
<body>
    <header class="gradient-bg text-white relative h-60">
        <div class="container mx-auto px-6 py-12 flex items-center justify-between">
            <a href="./../index.php" class="flex items-center">
                <img src="./public/logo.svg" alt="Logo" class="h-12 w-12 mr-3">
                <span class="text-2xl font-bold font-display">DSI - Gestion de Présence</span>
            </a>
            <div class="text-right">
                <div class="text-sm opacity-75">
                    <span id="current-date">Chargement de la date...</span>
                </div>
                <div class="text-xl font-bold">
                    <span id="current-time">00:00:00</span>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-bold text-congo-green-dark mb-4">Scan de Présence</h1>
                <p class="text-lg text-ui-text-muted mb-6">Scannez votre code QR ou entrez votre matricule pour enregistrer votre présence.</p>
            </div>

            <div class="method-tabs">
                <div id="tab-qr" class="method-tab active" onclick="switchMethod('qr')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Scanner QR
                </div>
                <?php if ($isLoggedIn): ?>
                <div id="tab-manual" class="method-tab" onclick="switchMethod('manual')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                    Matricule
                </div>
                <?php else: ?>
                <a href="login.php?from=scan" class="method-tab">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                    Matricule
                </a>
                <?php endif; ?>
            </div>

            <div id="qr-container" class="method-container bg-white rounded-3xl shadow-xl p-6 md:p-8 card-shine border border-congo-green-light">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 rounded-xl gradient-bg flex items-center justify-center mr-4 pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-congo-green-dark">Scanner votre QR Code</h2>
                </div>

                <div class="relative mb-8 bg-congo-yellow-pale p-4 rounded-2xl">
                    <div id="qr-reader" class="rounded-xl overflow-hidden"></div>
                    <div class="scan-line"></div>
                    <div class="scan-overlay">
                        <div class="corner corner-top-left"></div>
                        <div class="corner corner-top-right"></div>
                        <div class="corner corner-bottom-left"></div>
                        <div class="corner corner-bottom-right"></div>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-sm text-ui-text-muted mb-4">Placez le QR code devant la caméra pour l'enregistrement automatique</p>
                    <button id="start-scanner" class="scan-btn start-scan-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Lancer le scan</span>
                    </button>
                    <button id="stop-scanner" class="scan-btn stop-scan-btn hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                        </svg>
                        <span>Arrêter le scan</span>
                    </button>
                </div>
            </div>

            <div id="manual-container" class="method-container bg-white rounded-3xl shadow-xl p-6 md:p-8 card-shine border border-congo-yellow hidden">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 rounded-xl gradient-bg flex items-center justify-center mr-4 pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-congo-green-dark">Saisir votre matricule</h2>
                </div>

                <form id="matricule-form" class="space-y-4">
                    <div class="relative">
                        <label for="matricule" class="block text-sm font-medium text-ui-text-muted mb-1">Numéro matricule</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" id="matricule" name="matricule" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-congo-green focus:border-congo-green" placeholder="Entrez votre matricule (ex: DSI-001)" required>
                        </div>
                    </div>
                    <div class="relative">
                        <label for="presence_date" class="block text-sm font-medium text-ui-text-muted mb-1">Date de présence</label>
                        <input type="date" id="presence_date" name="presence_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-congo-green focus:border-congo-green" required>
                    </div>
                    <div class="relative">
                        <label for="presence_time" class="block text-sm font-medium text-ui-text-muted mb-1">Heure de présence</label>
                        <input type="time" id="presence_time" name="presence_time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-congo-green focus:border-congo-green" required>
                    </div>
                    <button type="submit" class="w-full custom-btn font-medium py-3 px-4 rounded-xl flex items-center justify-center">
                        <span>Valider le matricule</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </form>

                <?php if ($isLoggedIn): ?>
                <div class="text-center mt-4">
                    <a href="logout.php" class="text-red-600 hover:text-red-800 font-medium transition-colors">Se déconnecter</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-8">
                <a href="./../index.php" class="text-congo-green hover:text-congo-green-light font-medium flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </main>

    <!-- Modal de confirmation -->
    <div id="agent-modal" class="modal">
        <div class="modal-container bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 overflow-hidden card-shine">
            <div class="relative">
                <div class="gradient-bg h-24"></div>
                <div class="absolute top-0 right-0 m-4">
                    <span id="scan-type" class="px-3 py-1 bg-white text-congo-green rounded-full text-sm font-medium">Type de scan</span>
                </div>
                <div class="absolute top-12 inset-x-0 flex justify-center">
                    <div class="w-24 h-24 rounded-full border-4 border-white overflow-hidden bg-white">
                        <img id="agent-photo" src="https://i.pravatar.cc/150?img=10" alt="Photo de l'agent" class="w-full h-full object-cover">
                    </div>
                </div>
                <div class="pt-16 p-6 text-center">
                    <h3 id="agent-name" class="text-2xl font-bold text-congo-green-dark mb-1">Nom de l'agent</h3>
                    <p id="agent-service" class="text-congo-green font-medium mb-6">Service</p>
                    <div id="confirmation-message" class="mb-4 font-medium text-congo-green">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="confirmation-text">Enregistrement confirmé</span>
                    </div>
                    <p class="text-sm text-ui-text-muted">Cette fenêtre se fermera automatiquement dans <span id="countdown">10</span> secondes</p>
                </div>
                <div class="progress-bar" id="progress-bar"></div>
            </div>
        </div>
    </div>

    <!-- Modal d'erreur -->
    <div id="error-modal" class="modal">
        <div class="modal-container bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 overflow-hidden card-shine">
            <div class="relative">
                <div class="error-gradient-bg h-24"></div>
                <div class="absolute top-12 inset-x-0 flex justify-center">
                    <div class="w-24 h-24 flex items-center justify-center rounded-full border-4 border-white bg-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-congo-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="pt-16 p-6 text-center">
                    <h3 class="text-2xl font-bold text-congo-green-dark mb-2">Erreur</h3>
                    <p id="error-message" class="text-ui-text-muted mb-6">Message d'erreur</p>
                    <button id="error-close-btn" class="px-6 py-2 bg-congo-red text-white rounded-xl font-medium hover:bg-red-700 transition-colors">
                        Fermer
                    </button>
                    <p class="text-sm text-ui-text-muted mt-4">Cette fenêtre se fermera automatiquement dans <span id="error-countdown">5</span> secondes</p>
                </div>
                <div class="progress-bar error-progress-bar" id="error-progress-bar"></div>
            </div>
        </div>
    </div>

    <footer class="footer-bg text-white py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0 flex items-center">
                    <img src="./public/logo.svg" alt="Logo" class="h-16 w-16 mr-4">
                    <div>
                        <h2 class="text-2xl font-bold font-display text-white">DSI - Gestion de Présence</h2>
                        <p class="text-gray-200 mt-2">Solution développée pour la Direction des Systèmes d'Information</p>
                    </div>
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="footer-link">Aide</a>
                    <a href="#" class="footer-link">Contact</a>
                    <a href="#" class="footer-link">Mentions légales</a>
                </div>
            </div>
            <hr class="footer-divider my-8">
            <div class="text-center flex flex-col items-center">
                <p class="text-white text-lg font-medium">© <?php echo date('Y'); ?> Direction des Systèmes d'Information</p>
                <p class="text-congo-yellow mt-2 font-medium">République du Congo - Brazzaville</p>
            </div>
        </div>
    </footer>

    <script>
        let html5QrCode = null;
        let isScanning = false;
        let currentMethod = 'qr';

        // Fonction pour lire un message audio
        function speakMessage(message, isError = false) {
            if ('speechSynthesis' in window) {
                // Annuler toute lecture en cours pour éviter les chevauchements
                speechSynthesis.cancel();
                
                const finalMessage = isError ? `Erreur, ${message}` : message;
                const utterance = new SpeechSynthesisUtterance(finalMessage);
                utterance.lang = 'fr-FR'; // Langue française
                utterance.volume = 1; // Volume (0 à 1)
                utterance.rate = 1; // Vitesse (0.1 à 10)
                utterance.pitch = 1; // Hauteur (0 à 2)
                speechSynthesis.speak(utterance);
            } else {
                console.warn("La synthèse vocale n'est pas supportée par ce navigateur.");
            }
        }

        function updateDateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('fr-FR', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('fr-FR', { hour12: false });
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        function switchMethod(method) {
            <?php if (!$isLoggedIn): ?>
            if (method === 'manual') {
                window.location.href = 'login.php?from=scan';
                return;
            }
            <?php endif; ?>

            if (isScanning && method !== currentMethod && currentMethod === 'qr') {
                stopQRScanner();
            }
            
            currentMethod = method;
            
            document.getElementById('tab-qr').classList.toggle('active', method === 'qr');
            <?php if ($isLoggedIn): ?>
            document.getElementById('tab-manual').classList.toggle('active', method === 'manual');
            <?php endif; ?>
            
            const qrContainer = document.getElementById('qr-container');
            const manualContainer = document.getElementById('manual-container');
            
            if (method === 'qr') {
                manualContainer.classList.add('hidden');
                qrContainer.classList.remove('hidden');
                void qrContainer.offsetWidth;
                qrContainer.classList.add('fade-enter');
                setTimeout(() => {
                    qrContainer.classList.add('fade-enter-active');
                }, 10);
                setTimeout(() => {
                    qrContainer.classList.remove('fade-enter', 'fade-enter-active');
                }, 300);
            } else {
                qrContainer.classList.add('hidden');
                manualContainer.classList.remove('hidden');
                void manualContainer.offsetWidth;
                manualContainer.classList.add('fade-enter');
                setTimeout(() => {
                    manualContainer.classList.add('fade-enter-active');
                }, 10);
                setTimeout(() => {
                    manualContainer.classList.remove('fade-enter', 'fade-enter-active');
                }, 300);
            }
        }

        function startQRScanner() {
            if (isScanning) return;
            
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }
            
            const qrCodeSuccessCallback = (matricule) => {
                console.log(`Code scanné: ${matricule}`);
                stopQRScanner();
                processPresence(matricule);
            };
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback)
                .then(() => {
                    console.log("Scanner démarré");
                    isScanning = true;
                    updateScanButtons();
                })
                .catch((err) => {
                    console.error("Erreur lors du démarrage du scanner:", err);
                    showErrorModal("Impossible d'accéder à la caméra. Veuillez vérifier les permissions.");
                    speakMessage("Impossible d'accéder à la caméra. Veuillez vérifier les permissions.", true);
                });
        }

        function stopQRScanner() {
            if (!html5QrCode || !isScanning) return;
            
            html5QrCode.stop()
                .then(() => {
                    console.log("Scanner arrêté");
                    isScanning = false;
                    updateScanButtons();
                })
                .catch((err) => {
                    console.error("Erreur lors de l'arrêt du scanner:", err);
                    showErrorModal("Erreur lors de l'arrêt du scanner.");
                    speakMessage("Erreur lors de l'arrêt du scanner.", true);
                });
        }

        function updateScanButtons() {
            const startButton = document.getElementById('start-scanner');
            const stopButton = document.getElementById('stop-scanner');
            
            if (isScanning) {
                startButton.classList.add('hidden');
                stopButton.classList.remove('hidden');
            } else {
                startButton.classList.remove('hidden');
                stopButton.classList.add('hidden');
            }
        }

        function processPresence(matricule) {
            const presenceDate = document.getElementById('presence_date')?.value || '';
            const presenceTime = document.getElementById('presence_time')?.value || '';
            
            const formData = new URLSearchParams();
            formData.append('matricule', matricule);
            if (presenceDate && presenceTime) {
                formData.append('presence_date', presenceDate);
                formData.append('presence_time', presenceTime);
            }
            
            fetch('process_presence.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                console.log("Données reçues:", data);
                
                if (data.success) {
                    const agentName = `${data.agent.prenom} ${data.agent.nom}`;
                    document.getElementById('agent-name').textContent = agentName;
                    document.getElementById('agent-service').textContent = data.agent.service;
                    document.getElementById('scan-type').textContent = data.type;
                    document.getElementById('scan-type').className = 
                        data.type === 'arrivée' 
                            ? 'px-3 py-1 bg-white text-congo-green rounded-full text-sm font-medium' 
                            : 'px-3 py-1 bg-white text-congo-yellow rounded-full text-sm font-medium';
                    document.getElementById('confirmation-text').textContent = 
                        data.type === 'arrivée' 
                            ? 'Arrivée enregistrée avec succès' 
                            : 'Départ enregistré avec succès';
                    
                    const agentPhoto = document.getElementById('agent-photo');
                    if (data.agent.photo && data.agent.photo !== null) {
                        agentPhoto.src = data.agent.photo;
                    } else {
                        agentPhoto.src = 'https://i.pravatar.cc/150?img=10';
                    }
                    
                    // Lire le message audio pour arrivée ou départ
                    const audioMessage = data.type === 'arrivée' 
                        ? `Bienvenue ${agentName}` 
                        : `Au revoir ${agentName}`;
                    speakMessage(audioMessage);
                    
                    if (currentMethod === 'manual') {
                        document.getElementById('matricule').value = '';
                        if (document.getElementById('presence_date')) document.getElementById('presence_date').value = '';
                        if (document.getElementById('presence_time')) document.getElementById('presence_time').value = '';
                    }
                    
                    showSuccessModal();
                } else {
                    showErrorModal(data.message || "Une erreur s'est produite lors du traitement de la présence.");
                    speakMessage(data.message || "Une erreur s'est produite lors du traitement de la présence.", true);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showErrorModal("Une erreur s'est produite lors de la communication avec le serveur.");
                speakMessage("Une erreur s'est produite lors de la communication avec le serveur.", true);
            });
        }

        function showSuccessModal() {
            const modal = document.getElementById('agent-modal');
            const progressBar = document.getElementById('progress-bar');
            let countdown = 10;
            let countdownElement = document.getElementById('countdown');
            
            modal.classList.add('active');
            progressBar.style.transition = 'transform 10s linear';
            progressBar.style.transform = 'scaleX(0)';
            countdownElement.textContent = countdown;
            
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    hideSuccessModal();
                }
            }, 1000);
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    clearInterval(countdownInterval);
                    hideSuccessModal();
                }
            });
        }

        function hideSuccessModal() {
            const modal = document.getElementById('agent-modal');
            const progressBar = document.getElementById('progress-bar');
            
            modal.classList.remove('active');
            setTimeout(() => {
                progressBar.style.transition = 'none';
                progressBar.style.transform = 'scaleX(1)';
            }, 300);
            
            if (currentMethod === 'qr') {
                startQRScanner();
            }
        }

        function showErrorModal(message) {
            const modal = document.getElementById('error-modal');
            const progressBar = document.getElementById('error-progress-bar');
            const errorMessage = document.getElementById('error-message');
            let countdown = 5;
            let countdownElement = document.getElementById('error-countdown');
            
            errorMessage.textContent = message;
            modal.classList.add('active');
            progressBar.style.transition = 'transform 5s linear';
            progressBar.style.transform = 'scaleX(0)';
            countdownElement.textContent = countdown;
            
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    hideErrorModal();
                }
            }, 1000);
            
            const closeBtn = document.getElementById('error-close-btn');
            closeBtn.onclick = () => {
                clearInterval(countdownInterval);
                hideErrorModal();
            };
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    clearInterval(countdownInterval);
                    hideErrorModal();
                }
            });
        }

        function hideErrorModal() {
            const modal = document.getElementById('error-modal');
            const progressBar = document.getElementById('error-progress-bar');
            
            modal.classList.remove('active');
            setTimeout(() => {
                progressBar.style.transition = 'none';
                progressBar.style.transform = 'scaleX(1)';
            }, 300);
            
            if (currentMethod === 'qr') {
                startQRScanner();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('start-scanner').addEventListener('click', startQRScanner);
            document.getElementById('stop-scanner').addEventListener('click', stopQRScanner);
            
            document.getElementById('matricule-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const matricule = document.getElementById('matricule').value.trim();
                
                if (matricule) {
                    processPresence(matricule);
                } else {
                    showErrorModal("Veuillez entrer un matricule valide.");
                    speakMessage("Veuillez entrer un matricule valide.", true);
                }
            });

            const scanBtns = document.querySelectorAll('.scan-btn');
            scanBtns.forEach(btn => {
                btn.classList.add('custom-btn', 'py-2', 'px-4', 'rounded-xl', 'flex', 'items-center', 'justify-center', 'w-full', 'md:w-auto');
            });
            
            document.querySelector('.start-scan-btn').classList.add('btn-arrival');
            document.querySelector('.stop-scan-btn').classList.add('btn-departure');
        });
    </script>
</body>
</html>