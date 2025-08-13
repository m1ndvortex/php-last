<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Jewelry Platform') }}</title>
    
    <!-- Persian Font -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />

    <!-- English Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="description" content="Bilingual Persian/English Jewelry Business Management Platform">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Jewelry Platform">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    
    <!-- Manifest -->
    <link rel="manifest" href="/manifest.json">
    <link rel="manifest" href="/manifest.webmanifest">
    
    <!-- Preload modules -->
    <link rel="modulepreload" crossorigin href="/js/chunk-300a47f1.js">
    <link rel="modulepreload" crossorigin href="/js/chunk-e8ade734.js">
    <link rel="modulepreload" crossorigin href="/js/chunk-63a63872.js">
    <link rel="modulepreload" crossorigin href="/js/chunk-e5f6a124.js">
    <link rel="modulepreload" crossorigin href="/js/chunk-9984d79b.js">
    <link rel="modulepreload" crossorigin href="/js/chunk-9edc2f6d.js">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/css/dashboard-components-bf5fbefd.css">
    <link rel="stylesheet" href="/css/inventory-components-ffde5f23.css">
    <link rel="stylesheet" href="/css/index-86343f46.css">
</head>
<body>
    <div id="app"></div>
    
    <!-- Vue.js Application -->
    <script type="module" crossorigin src="/js/index-510f3f55.js"></script>
    
    <!-- PWA Service Worker -->
    <script id="vite-plugin-pwa:register-sw" src="/registerSW.js"></script>
</body>
</html>