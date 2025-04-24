<?php
// Inclure le fichier de vérification d'authentification
require_once './src/auth_check.php';

// Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
if (isLoggedIn()) {
    header('Location: ./src/dashboard.php');
    exit();
}

// Le reste du code HTML de l'index.php reste inchangé...
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSI - Gestion de Présence</title>
    <!-- Inclusion directe de Tailwind (pré-compilé) - à remplacer par votre build pour la production -->
    <link href="./src/assets/css/tailwind.min.css" rel="stylesheet">
    <!-- Polices personnalisées -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #038C33 0%, #014023 100%);
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
            pointer-events: none; /* Empêche cet élément de bloquer les interactions */
        }
        .card-shine:hover::after {
            transform: rotate(30deg) translate(0, 50%);
        }
        .wave-shape {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 15vh;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C57.32,118.92,163.58,122.58,241.16,119.49,382.88,113.33,383.09,91.3,443.95,76.39c43.35-10.65,83.93-15.07,127.44-19.95Z' fill='%23ffffff' opacity='0.3'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
        }
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        /* Style des boutons */
        .custom-btn {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #038C33 0%, #014023 100%);
            color: white;
            position: relative; /* Assure que le bouton est bien positionné */
            z-index: 10; /* Place le bouton au-dessus des autres éléments */
        }
        .custom-btn:hover {
            background: #E9F2A0 !important;
            color: #014023 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer; /* Curseur pointer au survol */
        }
        /* Style spécifique pour Découvrir */
        .btn-decouvrir {
            background: white;
            color: #038C33;
        }
        .btn-decouvrir:hover {
            background: #F2CE16 !important;
            color: #014023 !important;
        }
        /* Assure que les éléments interactifs sont accessibles */
        form, a.custom-btn {
            position: relative;
            z-index: 20;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
        }
        /* Style amélioré du footer */
        .footer-bg {
            background-color: #01301A; /* Encore plus foncé que le vert foncé standard */
        }
        .footer-link {
            color: #F2CE16; /* Jaune vif */
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .footer-link:hover {
            color: #FFFFFF;
            text-decoration: underline;
        }
        .footer-divider {
            border-color: #038C33; /* Vert standard pour la ligne */
            opacity: 0.5;
            height: 2px;
        }
    </style>
</head>
<body>
    <header class="gradient-bg text-white relative h-screen">
    <div class="container mx-auto px-6 py-12 md:py-24 flex flex-col h-full justify-center">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="w-full md:w-1/2 mb-12 md:mb-0">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    <span class="block">Gestion de Présence</span>
                    <span class="block text-congo-yellow">Direction des Systèmes d'Information</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-congo-yellow-pale">
                    Optimisez le suivi de présence des agents avec notre solution moderne et efficace.
                </p>
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="#options" class="custom-btn btn-decouvrir font-semibold px-6 py-3 rounded-lg shadow-lg inline-block">
                        Découvrir
                    </a>
                </div>
            </div>
            <div class="w-full md:w-1/2">
                <div class="bg-white bg-opacity-20 p-8 rounded-2xl backdrop-blur-sm shadow-xl">
                    <!-- Illustration SVG intégrée avec logo du Congo de manière créative -->
                    <svg class="w-full floating" viewBox="0 0 1600 1200" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="headerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#5CBF15" />
                                <stop offset="100%" stop-color="#014023" />
                            </linearGradient>
                            <linearGradient id="screenGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#E9F2A0" />
                                <stop offset="100%" stop-color="#F2CE16" />
                            </linearGradient>
                            <!-- Masque pour intégrer le logo dans un cercle lumineux -->
                            <mask id="logoMask">
                                <rect width="100%" height="100%" fill="white"/>
                                <circle cx="800" cy="250" r="120" fill="black"/>
                            </mask>
                            <!-- Filtre pour l'effet de lueur autour du logo -->
                            <filter id="logoGlow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="15" result="blur"/>
                                <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                            </filter>
                            <!-- Clip path pour les éléments du Congo -->
                            <clipPath id="congoShape">
                                <path d="M400,400 Q800,200 1200,400 L1200,900 Q800,1000 400,900 Z" />
                            </clipPath>
                        </defs>
                        
                        <!-- Background avec motifs inspirés des couleurs du Congo -->
                        <rect width="100%" height="100%" fill="#014023" opacity="0.05"/>
                        <path d="M0,300 Q400,200 800,300 T1600,300 V1200 H0 Z" fill="#F2CE16" opacity="0.1"/>
                        <path d="M0,500 Q400,600 800,500 T1600,500 V1200 H0 Z" fill="#014023" opacity="0.1"/>
                        
                        <!-- Cercle lumineux derrière le logo -->
                        <circle cx="800" cy="250" r="150" fill="#F2CE16" opacity="0.2" filter="url(#logoGlow)"/>
                        
                        <!-- Logo du Congo en position centrale et dominant -->
                        <image href="./src/public/logo.svg" x="650" y="100" width="300" height="300" filter="url(#logoGlow)"/>
                        
                        <!-- Main screen/device avec le bord aux couleurs du Congo -->
                        <rect x="400" y="380" width="800" height="600" rx="20" fill="#ffffff" stroke="#014023" stroke-width="8"/>
                        
                        <!-- Bordure aux couleurs du drapeau du Congo -->
                        <rect x="400" y="380" width="800" height="10" fill="#009543"/>
                        <rect x="400" y="390" width="800" height="10" fill="#fbde4a"/>
                        <rect x="400" y="400" width="800" height="10" fill="#dc241f"/>
                        
                        <rect x="450" y="430" width="700" height="500" rx="10" fill="url(#screenGradient)"/>
                        
                        <!-- Titre avec motif aux couleurs du Congo -->
                        <rect x="500" y="450" width="600" height="60" rx="5" fill="#014023"/>
                        <text x="800" y="490" font-family="Montserrat, sans-serif" font-size="24" fill="#F2CE16" text-anchor="middle" font-weight="bold">SYSTÈME DE GESTION DE PRÉSENCE</text>
                        
                        <!-- Screen content - attendance tracking visual -->
                        <rect x="500" y="530" width="600" height="50" rx="5" fill="#ffffff"/>
                        <circle cx="550" cy="555" r="15" fill="#038C33"/>
                        <rect x="590" y="545" width="150" height="20" rx="5" fill="#038C33" opacity="0.7"/>
                        <rect x="850" y="545" width="100" height="20" rx="5" fill="#0D0D0D" opacity="0.7"/>
                        
                        <rect x="500" y="590" width="600" height="50" rx="5" fill="#ffffff"/>
                        <circle cx="550" cy="615" r="15" fill="#D91A1A"/>
                        <rect x="590" y="605" width="150" height="20" rx="5" fill="#038C33" opacity="0.7"/>
                        <rect x="850" y="605" width="100" height="20" rx="5" fill="#0D0D0D" opacity="0.7"/>
                        
                        <rect x="500" y="650" width="600" height="50" rx="5" fill="#ffffff"/>
                        <circle cx="550" cy="675" r="15" fill="#038C33"/>
                        <rect x="590" y="665" width="150" height="20" rx="5" fill="#038C33" opacity="0.7"/>
                        <rect x="850" y="665" width="100" height="20" rx="5" fill="#0D0D0D" opacity="0.7"/>
                        
                        <rect x="500" y="710" width="600" height="50" rx="5" fill="#ffffff"/>
                        <circle cx="550" cy="735" r="15" fill="#F2CE16"/>
                        <rect x="590" y="725" width="150" height="20" rx="5" fill="#038C33" opacity="0.7"/>
                        <rect x="850" y="725" width="100" height="20" rx="5" fill="#0D0D0D" opacity="0.7"/>
                        
                        <!-- QR code intégrant les motifs du Congo -->
                        <rect x="500" y="780" width="140" height="140" fill="#ffffff" stroke="#038C33" stroke-width="3"/>
                        <g transform="translate(520, 800)">
                            <!-- QR code stylisé aux couleurs du Congo -->
                            <rect x="0" y="0" width="100" height="100" fill="#014023" opacity="0.9"/>
                            <rect x="10" y="10" width="20" height="20" fill="#F2CE16"/>
                            <rect x="70" y="10" width="20" height="20" fill="#F2CE16"/>
                            <rect x="10" y="70" width="20" height="20" fill="#F2CE16"/>
                            <rect x="40" y="40" width="20" height="20" fill="#F2CE16"/>
                            <!-- Mini drapeau du Congo au centre du QR code -->
                            <rect x="40" y="10" width="20" height="20" fill="#009543"/>
                            <rect x="40" y="10" width="20" height="6.67" fill="#fbde4a"/>
                            <rect x="40" y="23.33" width="20" height="6.67" fill="#dc241f"/>
                        </g>
                        
                        <!-- Statistiques visualisées avec couleurs nationales -->
                        <rect x="660" y="780" width="440" height="140" rx="10" fill="#ffffff" stroke="#e5e7eb" stroke-width="2"/>
                        
                        <!-- Graphique circulaire aux couleurs du Congo -->
                        <circle cx="730" cy="850" r="50" fill="transparent" stroke="#e5e7eb" stroke-width="10"/>
                        <path d="M730,850 L730,800 A50,50 0 0,1 773.3,825 Z" fill="#009543"/>
                        <path d="M730,850 L773.3,825 A50,50 0 0,1 756.7,893.3 Z" fill="#fbde4a"/>
                        <path d="M730,850 L756.7,893.3 A50,50 0 0,1 680,875 Z" fill="#dc241f"/>
                        <path d="M730,850 L680,875 A50,50 0 0,1 730,800 Z" fill="#014023"/>
                        <circle cx="730" cy="850" r="25" fill="white"/>
                        <text x="730" y="855" font-family="Inter, sans-serif" font-size="14" fill="#014023" text-anchor="middle" font-weight="bold">75%</text>
                        
                        <!-- Barres de statistiques -->
                        <rect x="820" y="810" width="250" height="20" rx="5" fill="#e5e7eb"/>
                        <rect x="820" y="810" width="175" height="20" rx="5" fill="#009543"/>
                        <text x="830" y="825" font-family="Inter, sans-serif" font-size="12" fill="white">Présents</text>
                        
                        <rect x="820" y="840" width="250" height="20" rx="5" fill="#e5e7eb"/>
                        <rect x="820" y="840" width="50" height="20" rx="5" fill="#dc241f"/>
                        <text x="830" y="855" font-family="Inter, sans-serif" font-size="12" fill="white">Absents</text>
                        
                        <rect x="820" y="870" width="250" height="20" rx="5" fill="#e5e7eb"/>
                        <rect x="820" y="870" width="75" height="20" rx="5" fill="#fbde4a"/>
                        <text x="830" y="885" font-family="Inter, sans-serif" font-size="12" fill="#014023">En mission</text>
                        
                        <!-- People figures représentant les citoyens du Congo -->
                        <g transform="translate(250, 700)">
                            <circle cx="50" cy="50" r="40" fill="#009543" opacity="0.7"/>
                            <rect x="10" y="100" width="80" height="120" rx="20" fill="#009543" opacity="0.7"/>
                            <!-- Écharpe aux couleurs du drapeau -->
                            <path d="M30,100 Q50,130 70,100" stroke="#fbde4a" stroke-width="8" fill="none"/>
                        </g>
                        
                        <g transform="translate(1300, 700)">
                            <circle cx="50" cy="50" r="40" fill="#F2CE16" opacity="0.7"/>
                            <rect x="10" y="100" width="80" height="120" rx="20" fill="#F2CE16" opacity="0.7"/>
                            <!-- Écharpe aux couleurs du drapeau -->
                            <path d="M30,100 Q50,130 70,100" stroke="#dc241f" stroke-width="8" fill="none"/>
                        </g>
                        
                        <!-- Connection lines aux couleurs du Congo -->
                        <path d="M350 750 Q 400 650 450 680" stroke="#009543" stroke-width="3" fill="none" stroke-dasharray="5,5"/>
                        <path d="M1250 750 Q 1200 650 1150 680" stroke="#dc241f" stroke-width="3" fill="none" stroke-dasharray="5,5"/>
                        
                        <!-- Animation de scan -->
                        <rect x="400" y="500" width="800" height="4" fill="#F2CE16" opacity="0.7">
                            <animate attributeName="y" from="380" to="976" dur="3s" repeatCount="indefinite"/>
                        </rect>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="wave-shape"></div>
</header>
    <section id="options" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-8 text-congo-green-dark">
                Accéder à la plateforme
            </h2>
            <p class="text-center text-ui-text-muted max-w-3xl mx-auto mb-16">Choisissez la méthode qui vous correspond pour vous connecter à notre système de gestion de présence intelligent et sécurisé.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
                <!-- Option 1: Connexion Dashboard (Redesigné avec couleurs Congo) -->
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden card-shine border border-congo-green-light">
                    <div class="h-4 gradient-bg"></div>
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 rounded-2xl gradient-bg flex items-center justify-center mr-4 pulse">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-congo-black">Espace Administration</h3>
                                <p class="text-congo-green">Accès sécurisé</p>
                            </div>
                        </div>
                        
                        <p class="text-ui-text-muted mb-8">Gérez l'ensemble des données de présence, configurez les règles et accédez aux rapports analytiques avancés.</p>
                        
                        <form action="./src/login.php" method="post" class="space-y-4">
                            <div class="relative">
                                <label for="email" class="block text-sm font-medium text-ui-text-muted mb-1">Email professionnel</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                    </div>
                                    <input type="email" id="email" name="email" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-congo-green focus:border-congo-green" placeholder="nom@entreprise.com" required>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <label for="password" class="block text-sm font-medium text-ui-text-muted mb-1">Mot de passe</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password" id="password" name="password" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-congo-green focus:border-congo-green" required>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <a href="forgot-password.php" class="text-sm font-medium text-congo-green hover:text-congo-green-light">
                                    Mot de passe oublié?
                                </a>
                            </div>
                            
                            <button type="submit" class="w-full custom-btn font-medium py-3 px-4 rounded-xl flex items-center justify-center">
                                <span>Se connecter</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Option 2: Accès scan présence (Redesigné avec couleurs Congo) -->
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden card-shine border border-congo-yellow">
                    <div class="h-4 gradient-bg"></div>
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 rounded-2xl gradient-bg flex items-center justify-center mr-4 pulse">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-congo-black">Scan de Présence</h3>
                                <p class="text-congo-green">Accès rapide</p>
                            </div>
                        </div>
                        
                        <p class="text-ui-text-muted mb-6">Enregistrez votre présence instantanément via le scan de QR code ou votre matricule. Rapide et sans authentification.</p>
                        
                        <!-- QR Code illustration -->
                        <div class="bg-congo-yellow-pale p-4 rounded-xl mb-6 text-center">
                            <svg class="w-32 h-32 mx-auto" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                <!-- QR Code pattern visual -->
                                <rect x="30" y="30" width="140" height="140" fill="white" stroke="#038C33" stroke-width="2"/>
                                <rect x="45" y="45" width="25" height="25" fill="#038C33"/>
                                <rect x="130" y="45" width="25" height="25" fill="#038C33"/>
                                <rect x="45" y="130" width="25" height="25" fill="#038C33"/>
                                <rect x="85" y="45" width="15" height="15" fill="#038C33"/>
                                <rect x="115" y="75" width="10" height="10" fill="#038C33"/>
                                <rect x="85" y="85" width="30" height="30" fill="#038C33"/>
                                <rect x="45" y="100" width="15" height="15" fill="#038C33"/>
                                <rect x="130" y="115" width="15" height="15" fill="#038C33"/>
                                <rect x="85" y="130" width="10" height="10" fill="#038C33"/>
                                <rect x="115" y="130" width="10" height="10" fill="#038C33"/>
                                <rect x="140" y="85" width="15" height="15" fill="#038C33"/>
                                <rect x="65" y="65" width="10" height="10" fill="#038C33"/>
                                
                                <!-- Scanning animation -->
                                <rect x="30" y="95" width="140" height="4" fill="#5CBF15" opacity="0.7">
                                    <animate attributeName="y" from="30" to="166" dur="2s" repeatCount="indefinite" />
                                </rect>
                            </svg>
                            <p class="text-sm text-ui-text-muted mt-2">Scanner pour enregistrer votre présence</p>
                        </div>
                        
                        <div class="text-center">
                            <a href="./src/scan-presence.php" class="custom-btn font-medium w-full py-3 px-6 rounded-xl flex items-center justify-center">
                                <span>Accéder au scan</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                            <p class="mt-4 text-sm text-ui-text-muted">Aucune connexion requise pour le personnel</p>
                        </div>
                    </div>
               b    </div>
            </div>
        </div>
    </section>

<footer class="text-white py-12" style="background-color: #011F12;">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-6 md:mb-0 flex items-center">
                <!-- Logo ajouté ici -->
                <img src="./src/public/logo.svg" alt="Logo" class="h-16 w-16 mr-4">
                <div>
                    <h2 class="text-2xl font-bold font-display text-white">DSI - Gestion de Présence</h2>
                    <p class="text-gray-200 mt-2">Solution développée pour la Direction des Systèmes d'Information</p>
                </div>
            </div>
            <div class="flex space-x-6">
                <a href="#" style="color: #F2CE16;" class="hover:text-white hover:underline text-lg font-medium">Aide</a>
                <a href="#" style="color: #F2CE16;" class="hover:text-white hover:underline text-lg font-medium">Contact</a>
                <a href="#" style="color: #F2CE16;" class="hover:text-white hover:underline text-lg font-medium">Mentions légales</a>
            </div>
        </div>
        <hr style="border-color: #038C33; opacity: 0.7;" class="my-8">
        <div class="text-center flex flex-col items-center">
            <p class="text-white text-lg font-medium">© <?php echo date('Y'); ?> Direction des Systèmes d'Information</p>
            <p style="color: #F2CE16;" class="mt-2 font-medium">République du Congo - Brazzaville</p>
        </div>
    </div>
</footer>
    <script>
        // Script pour l'animation du défilement doux
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>