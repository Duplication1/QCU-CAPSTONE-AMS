<?php
/**
 * Firebase Configuration
 * 
 * To set up:
 * 1. Go to Firebase Console (https://console.firebase.google.com/)
 * 2. Create a new project or select existing
 * 3. Go to Project Settings > General > Your apps > Web app
 * 4. Copy the config values and paste below
 * 5. Go to Realtime Database and create database in test mode (change rules later)
 */

// Firebase Web API Configuration
define('FIREBASE_CONFIG', [
    'apiKey' => "YOUR_API_KEY",
    'authDomain' => "YOUR_PROJECT_ID.firebaseapp.com",
    'databaseURL' => "https://YOUR_PROJECT_ID-default-rtdb.firebaseio.com",
    'projectId' => "YOUR_PROJECT_ID",
    'storageBucket' => "YOUR_PROJECT_ID.appspot.com",
    'messagingSenderId' => "YOUR_MESSAGING_SENDER_ID",
    'appId' => "YOUR_APP_ID"
]);

/**
 * Firebase Database Rules (Apply these in Firebase Console):
 * 
 * {
 *   "rules": {
 *     "pc_health": {
 *       ".read": "auth != null",
 *       ".write": "auth != null",
 *       "$pc_id": {
 *         ".write": "auth != null"
 *       }
 *     }
 *   }
 * }
 */
