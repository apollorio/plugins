<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cryptid Collective | Field Researcher Authentication</title>
  <link rel="stylesheet" href="style.css">
  <!-- Google Fonts - using fonts that match the brand description -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Space+Mono&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>:root {
  /* Primary Colors */
  --expedition-green: #1E3B2C;
  --midnight-charcoal: #2D3033;
  --misty-gray: #607D8B;
  
  /* Accent Colors */
  --bioluminescent-teal: #39F0D9;
  --evidence-amber: #FF9800;
  --scanner-green: #76FF03;
  
  /* Fonts */
  --font-heading: 'Rajdhani', sans-serif; /* Similar to described "Expedition" font */
  --font-body: 'Work Sans', sans-serif; /* Similar to described "Field Notes" font */
  --font-mono: 'Space Mono', monospace; /* Similar to described "Researcher's Log" font */
  
  /* Effects */
  --glass-opacity: 0.15;
  --blur-amount: 12px;
  --glow-strength: 3px;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: var(--font-body);
  color: #fff;
  line-height: 1.6;
  min-height: 100vh;
  position: relative;
  overflow-x: hidden;
  transition: background 0.5s ease, color 0.5s ease, all 0.8s ease;
}

/* Security State Color Transitions */
body {
  transition: all 0.8s ease;
}

.background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -2;
  background: linear-gradient(135deg, 
    var(--midnight-charcoal), 
    var(--expedition-green)
  );
  animation: backgroundPulse 15s ease-in-out infinite;
  transition: background 0.8s ease;
}

.glass-panel {
  transition: all 0.8s ease;
}

/* Different security states affect all elements using CSS variables */
input, button, a, .classified-stripe, .particle, .scanner-line {
  transition: all 0.8s ease;
}

/* Ensure radar ping uses theme colors */
.radar-ping {
  border-color: var(--bioluminescent-teal);
}

/* Make scanner line use theme colors */
.scanner-line {
  background: linear-gradient(
    90deg,
    transparent,
    var(--bioluminescent-teal),
    transparent
  ) !important;
}

/* Warning State Styles */
.warning-pulse {
  animation: warningPulse 3s infinite alternate;
}

@keyframes warningPulse {
  0%, 100% { box-shadow: var(--container-shadow); }
  50% { box-shadow: 0 4px 30px rgba(255, 193, 7, 0.3), 0 0 50px rgba(255, 193, 7, 0.3), inset 0 0 20px rgba(255, 193, 7, 0.2); }
}

/* Danger State Styles */
.danger-pulse {
  animation: dangerPulse 1.5s infinite alternate;
}

@keyframes dangerPulse {
  0%, 100% { box-shadow: var(--container-shadow); }
  50% { box-shadow: 0 4px 30px rgba(244, 67, 54, 0.4), 0 0 50px rgba(244, 67, 54, 0.4), inset 0 0 20px rgba(244, 67, 54, 0.3); }
}

/* Make the input focus colors match the security level */
body[data-security-level="warning"] input:focus {
  border-color: var(--bioluminescent-teal) !important;
  box-shadow: 0 0 8px var(--bioluminescent-teal) !important;
}

body[data-security-level="danger"] input:focus {
  border-color: var(--bioluminescent-teal) !important;
  box-shadow: 0 0 8px var(--bioluminescent-teal) !important;
}

/* Make particles match security level */
body[data-security-level="warning"] .particle {
  background-color: var(--particle-color) !important;
}

body[data-security-level="danger"] .particle {
  background-color: var(--particle-color) !important;
}

/* Ensure container backgrounds are correct */
body[data-security-level="warning"] .glass-panel {
  background: var(--container-bg) !important;
}

body[data-security-level="danger"] .glass-panel {
  background: var(--container-bg) !important;
}

.noise {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%" height="100%" filter="url(%23noiseFilter)" opacity="0.15"/%3E%3C/svg%3E');
  opacity: 0.3;
  z-index: -1;
  mix-blend-mode: overlay;
  animation: noiseShift 0.5s steps(2) infinite;
  pointer-events: none; /* Make sure this doesn't capture clicks */
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(circle at 20% 80%, rgba(57, 240, 217, 0.15), transparent 25%),
    radial-gradient(circle at 80% 20%, rgba(255, 152, 0, 0.1), transparent 25%);
  z-index: -1;
  pointer-events: none; /* Make sure this doesn't capture clicks */
}

.gradient-overlay::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(
    circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
    rgba(57, 240, 217, 0.15),
    transparent 35%
  );
  transition: opacity 0.3s ease;
  pointer-events: none; /* Make sure this doesn't capture clicks */
}

/* Additional Thematic Elements */
.topographic-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('data:image/svg+xml,%3Csvg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="topo" patternUnits="userSpaceOnUse" width="200" height="200"%3E%3Cpath d="M0,100 Q50,50 100,100 Q150,150 200,100 M0,50 Q50,0 100,50 Q150,100 200,50 M0,150 Q50,100 100,150 Q150,200 200,150" fill="none" stroke="rgba(57, 240, 217, 0.07)" stroke-width="0.5"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100%" height="100%" fill="url(%23topo)"/"%3E%3C/svg%3E');
  z-index: -1;
  opacity: 0.4;
  pointer-events: none; /* Make sure this doesn't capture clicks */
}

.classified-stamp {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  font-family: var(--font-mono);
  color: rgba(255, 152, 0, 0.2);
  border: 1px solid rgba(255, 152, 0, 0.2);
  padding: 0.2rem 0.4rem;
  font-size: 0.6rem;
  transform: rotate(12deg);
  text-transform: uppercase;
  letter-spacing: 1px;
  pointer-events: none; /* Make sure this doesn't capture clicks */
  z-index: 10;
  opacity: 0.15;
}

body[data-security-level="warning"] .classified-stamp {
  color: rgba(255, 193, 7, 0.3);
  border-color: rgba(255, 193, 7, 0.3);
  content: "WARNING: CLEARANCE BREACH";
}

body[data-security-level="danger"] .classified-stamp {
  color: rgba(244, 67, 54, 0.4);
  border-color: rgba(244, 67, 54, 0.4);
  content: "DANGER: UNAUTHORIZED ACCESS";
}

.redacted-text {
  position: relative;
}

.redacted-text::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: #fff;
  transform: translateY(-50%);
}

.coordinates-grid {
  position: relative;
  display: inline-block;
}

.grid-line {
  position: absolute;
  background-color: rgba(57, 240, 217, 0.2);
}

.grid-line:nth-child(1) {
  width: 100%;
  height: 1px;
  top: -5px;
  left: 0;
}

.grid-line:nth-child(2) {
  width: 1px;
  height: 100%;
  top: 0;
  right: -5px;
}

.morse-code {
  display: none; /* Hide completely */
}

.hidden-cryptid {
  position: absolute;
  bottom: 10px;
  right: 10px;
  width: 80px;
  height: 40px;
  background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 40"%3E%3Cpath d="M20,30 C30,10 50,10 60,30 C50,15 30,15 20,30 Z M25,20 A2,2 0 1,1 29,20 A2,2 0 1,1 25,20 Z" fill="rgba(57, 240, 217, 0.05)"/%3E%3C/svg%3E');
  opacity: 0.2;
  transition: opacity 0.5s ease;
  pointer-events: none; /* Make sure this doesn't capture clicks */
}

.hidden-cryptid:hover {
  opacity: 0.5;
}

.scan-animation {
  display: none; /* Hide completely */
}

.binary-code {
  display: none;
}

/* Layout */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  padding-top: 5vh; /* Add some space at the top */
  justify-content: center; /* Center content vertically */
  min-height: 100vh; /* Make container fill viewport height */
}

.container::before {
  content: none; /* Remove "CRYPTID COLLECTIVE" from top-right */
}

header {
  display: none; /* Remove the entire navbar/header area */
}

/* Enhanced glassmorphism */
.glass-panel {
  background: rgba(45, 48, 51, 0.1);
  backdrop-filter: blur(20px) contrast(120%);
  border: 1px solid rgba(255, 255, 255, 0.05);
  box-shadow: 
    0 4px 30px rgba(0, 0, 0, 0.2),
    0 0 50px rgba(57, 240, 217, 0.1),
    inset 0 0 15px rgba(57, 240, 217, 0.05);
  border-radius: 10px;
  padding: 2.5rem;
  position: relative;
  overflow: hidden;
  max-width: 550px;
  margin: auto; /* Center vertically when possible */
  width: 100%;
  transform-style: preserve-3d;
  perspective: 1000px;
  transition: background 0.5s ease, box-shadow 0.5s ease, border-color 0.5s ease, all 0.8s ease;
}

.glass-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
}

.glass-panel::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    transparent 30%,
    rgba(57, 240, 217, 0.03) 40%,
    rgba(57, 240, 217, 0.03) 60%,
    transparent 70%
  );
  z-index: 1; /* Ensure the shine is above other content */
  pointer-events: none; /* Make sure clicks pass through */
}

.glass-panel .shine-effect {
  position: absolute;
  top: -100%;
  left: -100%;
  width: 150%;
  height: 200%;
  background: linear-gradient(
    225deg,
    transparent 30%,
    rgba(255, 255, 255, 0.03) 50%,
    transparent 70%
  );
  transform: rotate(35deg);
  transition: transform 1.5s cubic-bezier(0.2, 0.8, 0.2, 1);
  pointer-events: none; /* Make sure clicks pass through */
  z-index: 1; /* Position it above content but below other effects */
}

.glass-panel:hover .shine-effect {
  transform: translateX(10%) rotate(35deg);
}

@keyframes subtleShine {
  0%, 100% { opacity: 0.03; }
  50% { opacity: 0.05; }
}

.glass-panel .shine-effect {
  animation: subtleShine 8s infinite;
}

/* Enhanced Security System Indicators */
.security-indicators {
  display: flex;
  gap: 6px;
  position: absolute;
  bottom: 12px; /* Position at the bottom of the button */
  right: 12px; /* Position on the right side of the button */
  z-index: 10;
}

.indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3); /* Lighter dots against button background */
  transition: all 0.5s cubic-bezier(0.2, 0, 0.4, 1);
}

.indicator.active {
  background: var(--bioluminescent-teal);
  box-shadow: 0 0 8px var(--bioluminescent-teal);
  animation: pulse 1s infinite;
}

.indicator.warning {
  background: var(--evidence-amber);
  box-shadow: 0 0 8px var(--evidence-amber);
  animation: pulse 0.5s infinite;
}

.indicator.danger {
  background: #f44336; /* Red color for danger */
  box-shadow: 0 0 8px #f44336;
  animation: pulse 0.3s infinite;
}

/* Authentication Notification */
.auth-notification {
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  padding: 10px 20px;
  border-radius: 4px;
  font-family: var(--font-mono);
  font-weight: bold;
  text-align: center;
  z-index: 100;
  animation: slideDown 0.3s forwards;
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
}

.auth-notification.error {
  background-color: rgba(244, 67, 54, 0.9);
  color: white;
}

.auth-notification.warning {
  background-color: rgba(255, 152, 0, 0.9);
  color: black;
  font-size: 1.05em;
}

.auth-notification.critical {
  background-color: rgba(153, 0, 0, 0.95); /* Darker, more opaque red */
  color: white;
  animation: criticalPulse 0.7s infinite;
  font-size: 1.1em;
  border: 1px solid #f44336;
  text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(4px);
  font-family: var(--font-mono);
  letter-spacing: 0.5px;
  position: relative;
}

.auth-notification.critical::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: repeating-linear-gradient(
    45deg,
    rgba(244, 67, 54, 0.1),
    rgba(244, 67, 54, 0.1) 10px,
    rgba(0, 0, 0, 0) 10px,
    rgba(0, 0, 0, 0) 20px
  );
  pointer-events: none;
}

.auth-notification.notice {
  background: rgba(57, 240, 217, 0.1);
  border: 1px solid rgba(57, 240, 217, 0.3);
  color: var(--bioluminescent-teal);
  box-shadow: 0 0 15px rgba(57, 240, 217, 0.2);
}

.auth-notification.success {
  background: rgba(57, 240, 217, 0.15);
  border: 1px solid var(--bioluminescent-teal);
  color: var(--bioluminescent-teal);
  animation: successPulse 2s infinite;
}

/* Dramatically Enhanced Lockout State */
.glass-panel.lockout {
  animation: lockoutPulse 1.5s infinite alternate;
  box-shadow: 
    0 4px 30px rgba(244, 67, 54, 0.4),
    0 0 50px rgba(244, 67, 54, 0.4),
    inset 0 0 15px rgba(244, 67, 54, 0.3);
  transition: all 0.5s ease;
  border-color: rgba(244, 67, 54, 0.6);
}

.glass-panel.shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

.glass-panel.lockout::after {
  background: linear-gradient(
    45deg,
    transparent 30%,
    rgba(244, 67, 54, 0.1) 40%,
    rgba(244, 67, 54, 0.1) 60%,
    transparent 70%
  );
  animation: redAlertScan 3s linear infinite;
}

.glass-panel.lockout input,
.glass-panel.lockout button {
  pointer-events: none;
  filter: grayscale(0.8);
  opacity: 0.8;
}

.lockout-message {
  display: none;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #f44336;
  font-family: var(--font-mono);
  font-size: 1.2rem;
  text-align: center;
  background: rgba(0, 0, 0, 0.85);
  padding: 2rem;
  border-radius: 4px;
  width: 90%;
  z-index: 20;
  animation: glitchText 0.2s infinite;
  border: 1px solid #f44336;
  box-shadow: 0 0 20px rgba(244, 67, 54, 0.5);
  text-shadow: 0 0 5px rgba(244, 67, 54, 0.8);
  letter-spacing: 1px;
}

.lockout-message .alert-prefix {
  color: #ff0000;
  font-weight: bold;
  font-size: 1.4em;
  animation: blink 1s infinite;
  display: block;
  margin-bottom: 10px;
}

.lockout-message span {
  display: block;
  font-size: 0.9rem;
  margin-top: 1rem;
  opacity: 0.9;
}

.lockout-message .small-text {
  font-size: 0.7rem;
  margin-top: 1.5rem;
  opacity: 0.6;
}

.glass-panel.lockout .lockout-message {
  display: block;
}

/* Security Breach Full Screen Effects - Enhanced Red Filter */
.security-breach {
  overflow: hidden; /* Prevent scrolling during lockout */
}

/* Add SVG filter to HTML */
body.security-breach {
  filter: url('#intense-red-filter') brightness(0.9) saturate(1.2);
  animation: redPulse 3s infinite alternate;
}

/* Make all page elements inherit the red filter */
body.security-breach * {
  filter: none; /* Prevent double-filtering */
}

/* Override any filters on specific elements */
body.security-breach .glass-panel,
body.security-breach .background,
body.security-breach .container,
body.security-breach .form-container,
body.security-breach input,
body.security-breach button {
  filter: none !important; /* Ensure no child elements override the body filter */
}

/* Intensify the red background color */
body.security-breach .background {
  animation: redBackgroundPulse 4s infinite alternate;
  background: linear-gradient(135deg, #661111, #4D0000) !important;
  opacity: 1;
}

/* Stronger red overlay for breach state */
body.security-breach::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 0, 0, 0.1);
  z-index: 5;
  pointer-events: none;
  mix-blend-mode: multiply;
}

/* More intense red pulse animation */
@keyframes redPulse {
  0% { filter: url('#intense-red-filter') brightness(0.9) saturate(1.2); }
  50% { filter: url('#intense-red-filter') brightness(0.8) saturate(1.3); }
  100% { filter: url('#intense-red-filter') brightness(0.9) saturate(1.2); }
}

/* More dramatic background animation */
@keyframes redBackgroundPulse {
  0% { opacity: 0.95; filter: brightness(0.9); }
  50% { opacity: 1; filter: brightness(1); }
  100% { opacity: 0.95; filter: brightness(0.9); }
}

.security-breach::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(80, 0, 0, 0.15);
  z-index: 5;
  pointer-events: none;
}

.security-flash {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 0, 0, 0.3);
  z-index: 999;
  pointer-events: none;
  animation: flash 1s infinite;
}

.breach-counter {
  position: fixed;
  top: 20px;
  left: 20px;
  background: rgba(0, 0, 0, 0.7);
  border: 1px solid #f44336;
  padding: 10px 15px;
  font-family: var(--font-mono);
  color: #f44336;
  font-size: 0.8rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: pulse 1s infinite;
  z-index: 1000;
}

.breach-counter .counter {
  font-size: 1.2rem;
  font-weight: bold;
  margin-top: 5px;
}

/* Dramatically Enhanced Security Breach Styles */
html.red-filter-active {
  filter: url('#red-filter');
}

html.red-pulse {
  animation: redPulseIntense 3s infinite alternate;
}

/* Security Breach Full Screen Effects - Dramatically Enhanced */
.security-breach {
  overflow: hidden; /* Prevent scrolling during lockout */
}

/* More intense red overlay */
.security-flash.critical-breach {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 0, 0, 0.4);
  z-index: 999;
  pointer-events: none;
  animation: criticalFlash 1.5s infinite;
  mix-blend-mode: multiply;
}

/* Add red biohazard pattern overlay */
body.security-breach::after {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"%3E%3Cpath d="M20,0 L20,40 M0,20 L40,20" stroke="rgba(255,0,0,0.15)" fill="none"/%3E%3C/svg%3E');
  opacity: 0.3;
  pointer-events: none;
  z-index: 998;
}

/* More dramatic background animation */
body.security-breach .background {
  animation: redBackgroundPulseIntense 4s infinite alternate !important;
  background: linear-gradient(135deg, #661111, #4D0000) !important;
  opacity: 1;
}

/* Change all text to red emergency colors */
body.security-breach h1,
body.security-breach p,
body.security-breach label,
body.security-breach span:not(.alert-prefix) {
  color: #ffcccc !important;
  text-shadow: 0 0 5px rgba(255, 0, 0, 0.7) !important;
}

/* More intense animations for security breach */
@keyframes redPulseIntense {
  0% { filter: url('#red-filter') brightness(0.9) saturate(1.2); }
  50% { filter: url('#red-filter') brightness(1.0) saturate(1.5); }
  100% { filter: url('#red-filter') brightness(0.8) saturate(1.3); }
}

@keyframes criticalFlash {
  0% { opacity: 0.1; }
  5% { opacity: 0.4; }
  10% { opacity: 0.1; }
  15% { opacity: 0.1; }
  20% { opacity: 0.3; }
  25% { opacity: 0.1; }
  35% { opacity: 0.5; }
  40% { opacity: 0.1; }
  100% { opacity: 0.1; }
}

@keyframes redBackgroundPulseIntense {
  0% { filter: brightness(0.7) saturate(1.4); }
  50% { filter: brightness(1.0) saturate(1.6); }
  100% { filter: brightness(0.7) saturate(1.4); }
}

/* Drain color from inputs during security breach */
body.security-breach input {
  color: #ff6666 !important;
  border-color: #990000 !important;
}

/* Make buttons appear "emergency" styled */
body.security-breach .btn-primary {
  background: rgba(255, 0, 0, 0.3) !important;
  border-color: rgba(255, 0, 0, 0.6) !important;
  color: #ffffff !important;
  box-shadow: 0 0 15px rgba(255, 0, 0, 0.4) !important;
}

/* COMPREHENSIVE RED SECURITY BREACH - top level filter applies to ALL content */
html.security-breach-active {
  filter: url('#security-breach-filter');
}

html.total-breach * {
  /* Override ANY color in the system during security breach */
  transition: all 0.1s !important;
}

/* Full screen overlay with warning pattern */
.breach-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 999;
  background-image: 
    repeating-linear-gradient(45deg, 
      rgba(255, 0, 0, 0.05) 0px,
      rgba(255, 0, 0, 0.05) 10px,
      rgba(0, 0, 0, 0) 10px,
      rgba(0, 0, 0, 0) 20px
    ),
    url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"%3E%3Cpath d="M25,0 L25,50 M0,25 L50,25" stroke="rgba(255,0,0,0.1)" stroke-width="2"/%3E%3C/svg%3E');
  mix-blend-mode: multiply;
  animation: redPulse 4s infinite alternate;
}

/* Force text color to red tones in breach mode */
html.total-breach h1,
html.total-breach p,
html.total-breach label,
html.total-breach a,
html.total-breach .btn-primary,
html.total-breach .classified-stamp,
html.total-breach input,
html.total-breach button,
html.total-breach span {
  color: #ff6666 !important;
  border-color: #990000 !important;
  text-shadow: 0 0 10px rgba(255, 0, 0, 0.7) !important;
}

/* Force backgrounds to red hues */
html.total-breach .glass-panel {
  background: rgba(70, 0, 0, 0.2) !important;
  box-shadow: 
    0 4px 30px rgba(255, 0, 0, 0.3),
    0 0 50px rgba(255, 0, 0, 0.2),
    inset 0 0 15px rgba(255, 0, 0, 0.1) !important;
  border-color: rgba(255, 0, 0, 0.3) !important;
}

/* Convert any green/teal glow to red */
html.total-breach .btn-primary {
  background: rgba(100, 0, 0, 0.3) !important;
  box-shadow: 0 0 15px rgba(255, 0, 0, 0.4) !important;
}

html.total-breach .btn-primary:hover {
  background: rgba(153, 0, 0, 0.4) !important;
  box-shadow: 0 0 20px rgba(255, 0, 0, 0.5) !important;
}

/* Make button glow red */
html.total-breach .btn-primary .btn-glow {
  background: linear-gradient(45deg, rgba(255, 0, 0, 0.5), transparent) !important;
}

/* Force classified stamp to red */
html.total-breach .classified-stamp {
  color: rgba(255, 0, 0, 0.7) !important;
  border-color: rgba(255, 0, 0, 0.7) !important;
  content: "EMERGENCY: UNAUTHORIZED ACCESS" !important;
  opacity: 0.8 !important;
}

/* Force the scanner line to be red */
html.total-breach .scanner-line {
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 0, 0, 0.8),
    transparent
  ) !important;
  animation-duration: 0.5s !important; /* Faster scanning */
  opacity: 0.8 !important;
  height: 3px !important;
}

/* Make particles red */
html.total-breach .particle {
  background-color: rgba(255, 0, 0, 0.6) !important;
  box-shadow: 0 0 3px rgba(255, 0, 0, 0.6) !important;
}

/* Make indicators red */
html.total-breach .indicator {
  background-color: rgba(255, 0, 0, 0.6) !important;
  box-shadow: 0 0 5px rgba(255, 0, 0, 0.6) !important;
}

/* Make text glow red */
html.total-breach .glitch-text::before,
html.total-breach .glitch-text::after {
  text-shadow: 2px 0 rgba(255, 0, 0, 0.7) !important;
}

/* Make radar pings red */
html.total-breach .radar-ping {
  border-color: rgba(255, 0, 0, 0.6) !important;
}

/* More intense red flashing */
@keyframes intensifiedRedFlash {
  0% { opacity: 0.1; }
  5% { opacity: 0.6; }
  10% { opacity: 0.1; }
  15% { opacity: 0.1; }
  20% { opacity: 0.4; }
  25% { opacity: 0.1; }
  35% { opacity: 0.7; }
  40% { opacity: 0.1; }
  100% { opacity: 0.1; }
}

/* Additional red pulse animation for the entire overlay */
@keyframes redPulse {
  0% { opacity: 0.7; }
  50% { opacity: 1.0; }
  100% { opacity: 0.7; }
}

/* Force inputs to have red focus styles */
html.total-breach input:focus {
  border-color: #990000 !important;
  box-shadow: 0 0 8px rgba(255, 0, 0, 0.4) !important;
}

/* Force input glow to be red */
html.total-breach .input-glow {
  background-color: rgba(153, 0, 0, 0.1) !important;
  box-shadow: 0 0 8px rgba(255, 0, 0, 0.4) !important;
}

/* Form styling */
.form-container {
  transition: all 0.4s ease;
  position: relative;
  z-index: 5; /* Ensure form is above decorative elements */
}

.form-container.hidden {
  display: none;
}

.input-container {
  display: flex;
  align-items: center;
  position: relative;
  margin-bottom: 0.5rem;
  z-index: 5; /* Ensure inputs are clickable */
}

.input-prefix {
  color: var(--bioluminescent-teal);
  font-family: var(--font-mono);
  font-size: 1.2rem;
  margin-right: 0.5rem;
  opacity: 0.7;
  position: absolute;
  left: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 5;
  user-select: none;
  pointer-events: none;
}

.input-prefix.secure {
  color: var(--evidence-amber);
}

.input-container input {
  width: 100%;
  padding: 0.75rem 1rem;
  padding-left: 2rem;
  border: 1px solid rgba(96, 125, 139, 0.3);
  border-radius: 4px;
  background: rgba(45, 48, 51, 0.3);
  color: #fff;
  font-family: var(--font-body);
  transition: border-color 0.3s ease, background-color 0.3s ease, all 0.8s ease;
  position: relative; /* Ensure proper stacking */
  z-index: 2; /* Higher than decorative elements */
}

/* Style date input */
input[type="date"] {
  font-family: var(--font-body);
  color: #fff;
  background: rgba(45, 48, 51, 0.3);
  border: 1px solid rgba(96, 125, 139, 0.3);
  border-radius: 4px;
  padding: 0.75rem 1rem;
  width: 100%;
}

/* Experimental typography */
h1 {
  font-family: var(--font-heading);
  color: #fff;
  font-size: clamp(1.8rem, 5vw, 2.5rem);
  line-height: 1;
  margin-bottom: 0.5rem;
  font-weight: 600;
  letter-spacing: -0.02em;
  transform: translateZ(0);
}

h1::before {
  content: attr(data-text);
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  opacity: 0.1;
  filter: blur(8px);
  transform: translateZ(-1px);
}

/* Modified classified stripe - remove indicators */
.classified-stripe {
  background-color: var(--evidence-amber);
  color: var(--midnight-charcoal);
  font-family: var(--font-mono);
  font-size: 0.7rem;
  font-weight: bold;
  padding: 0.25rem 0.75rem;
  display: inline-block; /* Changed from inline-flex */
  margin-bottom: 1.5rem;
  letter-spacing: 1px;
  transition: all 0.8s ease;
}

.form-group {
  position: relative;
  margin-bottom: 1.5rem;
  opacity: 0;
  transform: translateY(10px);
  animation: fadeInUp 0.5s forwards;
}

.protocol-acceptance {
  margin: 2rem 0;
  position: relative;
  z-index: 4;
}

.form-group.scanned::before {
  content: '';
  position: absolute;
  top: 0;
  left: -10px;
  width: calc(100% + 20px);
  height: 2px;
  background: linear-gradient(90deg, 
    transparent, 
    var(--bioluminescent-teal),
    transparent
  );
  animation: scanLine 0.5s ease-out forwards;
  opacity: 0.5;
  z-index: 1;
}

@keyframes scanLine {
  0% { transform: translateY(0); opacity: 0.7; }
  100% { transform: translateY(100%); opacity: 0; }
}

@keyframes fadeInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

label {
  display: block;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  color: var(--misty-gray);
}

/* Enhanced form elements */
.input-container::before {
  display: none; /* Remove the gradient effect */
}

input:focus {
  outline: none;
  border-color: var(--bioluminescent-teal);
  background: rgba(45, 48, 51, 0.5);
}

.input-glow {
  display: none; /* Remove the glow effect */
}

.form-actions {
  display: block;
  margin-top: 1.5rem;
}

/* Improved Checkbox Design - Attempt 2 */
.checkbox-container {
  position: relative; /* Crucial for absolute positioning of children */
  display: inline-flex; /* Use flex to align items nicely */
  align-items: center;
  padding-left: 35px; /* Keep space for the checkmark */
  margin: 10px 0;
  cursor: pointer;
  font-size: 0.9rem;
  user-select: none;
  color: var(--misty-gray);
  min-height: 22px; /* Ensure it has height */
  z-index: 5; /* Base z-index for the container */
}

.checkbox-container input {
  position: absolute;
  opacity: 0; /* Keep it invisible */
  cursor: pointer;
  /* Make the invisible input cover the entire label area */
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  margin: 0; /* Reset margin */
  padding: 0; /* Reset padding */
  z-index: 7; /* Highest z-index: This invisible element should receive the click */
}

.checkmark {
  position: absolute;
  top: 50%;
  left: 0; /* Position at the start of the padding */
  transform: translateY(-50%);
  height: 22px;
  width: 22px;
  background: rgba(45, 48, 51, 0.8);
  border: 1px solid rgba(57, 240, 217, 0.4);
  border-radius: 4px;
  transition: all 0.3s ease;
  display: block;
  z-index: 6; /* Below the input, above the container background */
  pointer-events: none; /* The checkmark itself shouldn't capture clicks */
}

/* Keep existing hover/checked styles for .checkmark */
.checkbox-container:hover .checkmark {
  background: rgba(57, 240, 217, 0.15);
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 10px rgba(57, 240, 217, 0.3);
}

.checkbox-container input:checked ~ .checkmark {
  background: rgba(57, 240, 217, 0.3);
  border-color: var(--bioluminescent-teal);
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 7px;
  top: 3px;
  width: 6px;
  height: 11px;
  border: solid var(--bioluminescent-teal);
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  /* No pointer events needed here either */
}

.checkbox-container input:checked ~ .checkmark:after {
  display: block;
}

/* Ensure the text span is also layered correctly if needed */
.checkbox-container span:not(.checkmark) {
  position: relative; /* Ensure text is part of the flow */
  z-index: 6; /* Same level as checkmark visual */
  pointer-events: none; /* Text shouldn't block clicks to the input */
}

/* Ensure form options have proper spacing and alignment */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 1rem 0;
  position: relative;
  z-index: 4;
}

/* Custom Toggle Element (Checkbox Alternative) */
.custom-toggle-container {
  display: flex;
  align-items: center;
  position: relative;
  padding-left: 35px;
  margin: 10px 0;
  cursor: pointer;
  font-size: 0.9rem;
  color: var(--misty-gray);
  user-select: none;
  min-height: 22px;
  z-index: 5;
}

.custom-toggle {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  height: 22px;
  width: 22px;
  background: rgba(45, 48, 51, 0.8);
  border: 1px solid rgba(57, 240, 217, 0.4);
  border-radius: 4px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.custom-toggle-container:hover .custom-toggle {
  background: rgba(57, 240, 217, 0.15);
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 10px rgba(57, 240, 217, 0.3);
}

.custom-toggle.checked {
  background: rgba(57, 240, 217, 0.3);
  border-color: var(--bioluminescent-teal);
}

.custom-toggle.checked::after {
  content: "";
  width: 6px;
  height: 11px;
  border: solid var(--bioluminescent-teal);
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  display: block;
  margin-bottom: 2px;
}

/* Hidden inputs for form submission */
.custom-toggle-container input {
  position: absolute;
  opacity: 0;
  height: 0;
  width: 0;
  pointer-events: none;
}

/* Buttons */
.btn-primary, .btn-secondary {
  font-family: var(--font-heading);
  border: none;
  border-radius: 4px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  cursor: pointer;
  position: relative;
  letter-spacing: 0.5px;
  overflow: hidden;
  transition: all 0.3s ease, background-color 0.5s ease, color 0.5s ease, border-color 0.5s ease, box-shadow 0.5s ease, all 0.8s ease;
  width: 100%;
  margin-bottom: 1rem;
}

.btn-primary {
  background: rgba(57, 240, 217, 0.2);
  color: var(--bioluminescent-teal);
  border: 1px solid rgba(57, 240, 217, 0.3);
  position: relative; /* Ensure positioning context for indicators */
}

.btn-secondary {
  background: rgba(96, 125, 139, 0.2);
  color: var(--misty-gray);
  border: 1px solid rgba(96, 125, 139, 0.2);
}

.btn-glow {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, var(--bioluminescent-teal), transparent);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.btn-primary:hover .btn-glow {
  opacity: 0.2;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 15px rgba(57, 240, 217, 0.3);
}

.btn-secondary:hover {
  background: rgba(96, 125, 139, 0.3);
}

/* Links */
.accent-link {
  color: var(--bioluminescent-teal);
  text-decoration: none;
  position: relative;
  transition: all 0.2s ease, background-color 0.5s ease, color 0.5s ease, border-color 0.5s ease, box-shadow 0.5s ease, all 0.8s ease;
}

.accent-link::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 1px;
  background-color: var(--bioluminescent-teal);
  transition: width 0.3s ease;
}

.accent-link:hover::after {
  width: 100%;
}

/* Form steps */
.form-step,
.specialty-options,
.equipment-selection,
.security-level,
.clearance-setup {
  display: none;
}

/* Footer */
footer {
  margin-top: 0;
  padding: 1rem 0;
  text-align: center;
}

.disclaimer {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--misty-gray);
  opacity: 0.6;
  letter-spacing: 1px;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scanLine {
  0% { transform: translateY(-100%); }
  100% { transform: translateY(100%); }
}

@keyframes backgroundPulse {
  0%, 100% { filter: hue-rotate(0deg); }
  50% { filter: hue-rotate(15deg); }
}

@keyframes surfaceShift {
  0% { transform: translateX(-100%) skewX(-15deg); }
  100% { transform: translateX(100%) skewX(-15deg); }
}

@keyframes noiseShift {
  0%, 100% { transform: translate(0, 0); }
  25% { transform: translate(4px, -4px); }
  50% { transform: translate(-4px, 4px); }
  75% { transform: translate(-4px, -4px); }
}

/* Experimental Effects */
.scanner-line {
  position: absolute;
  width: 100%;
  height: 2px;
  background: linear-gradient(
    90deg,
    transparent,
    var(--bioluminescent-teal),
    transparent
  );
  animation: scannerMove 2s linear infinite;
  opacity: 0.5;
  pointer-events: none; /* Make sure this doesn't capture clicks */
  transition: all 0.8s ease;
}

.glitch-text {
  position: relative;
  animation: textGlitch 0.2s infinite;
}

.glitch-text::before,
.glitch-text::after {
  content: attr(data-text);
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  clip: rect(0, 900px, 0, 0);
}

.glitch-text::before {
  text-shadow: -2px 0 var(--evidence-amber);
  animation: glitchEffect1 3s infinite linear alternate-reverse;
}

.glitch-text::after {
  text-shadow: 2px 0 var(--scanner-green);
  animation: glitchEffect2 2s infinite linear alternate-reverse;
}

.radar-ping {
  position: absolute;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  border: 2px solid var(--bioluminescent-teal);
  transform: translate(-50%, -50%);
  animation: radarPing 2s cubic-bezier(0, 0.2, 0.8, 1) infinite;
  pointer-events: none;
}

.particle-field {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none; /* Make sure this doesn't capture clicks */
}

.particle {
  position: absolute;
  background: var(--bioluminescent-teal);
  width: 2px;
  height: 2px;
  border-radius: 50%;
  filter: blur(1px);
  animation: particleFloat 20s linear infinite;
  transition: all 0.8s ease;
}

/* New Animations */
@keyframes scannerMove {
  0% { transform: translateY(-100%); opacity: 0; }
  50% { opacity: 0.5; }
  100% { transform: translateY(100%); opacity: 0; }
}

@keyframes glitchEffect1 {
  0% { clip: rect(44px, 900px, 56px, 0); }
  20% { clip: rect(12px, 900px, 65px, 0); }
  40% { clip: rect(78px, 900px, 82px, 0); }
  60% { clip: rect(32px, 900px, 39px, 0); }
  80% { clip: rect(92px, 900px, 98px, 0); }
  100% { clip: rect(21px, 900px, 28px, 0); }
}

@keyframes glitchEffect2 {
  0% { clip: rect(21px, 900px, 28px, 0); }
  20% { clip: rect(92px, 900px, 98px, 0); }
  40% { clip: rect(32px, 900px, 39px, 0); }
  60% { clip: rect(78px, 900px, 82px, 0); }
  80% { clip: rect(12px, 900px, 65px, 0); }
  100% { clip: rect(44px, 900px, 56px, 0); }
}

@keyframes radarPing {
  0% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(0);
  }
  100% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1);
  }
}

@keyframes particleFloat {
  from {
    transform: translateY(-10px) translateX(0);
  }
  to {
    transform: translateY(110vh) translateX(20px);
  }
}

@keyframes scanHorizontal {
  0% { top: -10px; left: -100%; }
  100% { top: 100%; left: 0; }
}

@keyframes redactionReveal {
  0% { width: 100%; }
  90% { width: 0; }
  100% { width: 0; }
}

@keyframes fadeInOut {
  0% { opacity: 0.1; }
  50% { opacity: 0.4; }
  100% { opacity: 0.1; }
}

@keyframes lockoutPulse {
  0% { background: rgba(45, 48, 51, 0.1); }
  50% { background: rgba(80, 20, 20, 0.2); }
  100% { background: rgba(120, 10, 10, 0.3); }
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}

@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}

@keyframes slideDown {
  from { top: -50px; opacity: 0; }
  to { top: 10px; opacity: 1; }
}

@keyframes criticalPulse {
  0% { background-color: #990000; }
  50% { background-color: #ff0000; }
  100% { background-color: #990000; }
}

@keyframes blink {
  0% { opacity: 1; }
  49% { opacity: 1; }
  50% { opacity: 0; }
  100% { opacity: 0; }
}

@keyframes flash {
  0% { opacity: 0; }
  5% { opacity: 0.7; }
  10% { opacity: 0; }
  15% { opacity: 0; }
  20% { opacity: 0.2; }
  25% { opacity: 0; }
  100% { opacity: 0; }
}

@keyframes redAlertScan {
  0% { 
    background-position: 0% 0%;
    opacity: 0.2;
  }
  50% {
    opacity: 0.5;
  }
  100% { 
    background-position: 200% 0%;
    opacity: 0.2;
  }
}

@keyframes warningPulse {
  0%, 100% { box-shadow: var(--container-shadow); }
  50% { box-shadow: 0 4px 30px rgba(255, 193, 7, 0.3), 0 0 50px rgba(255, 193, 7, 0.2), inset 0 0 20px rgba(255, 193, 7, 0.1); }
}

@keyframes dangerPulse {
  0%, 100% { box-shadow: var(--container-shadow); }
  50% { box-shadow: 0 4px 30px rgba(244, 67, 54, 0.3), 0 0 50px rgba(244, 67, 54, 0.2), inset 0 0 20px rgba(244, 67, 54, 0.1); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .container {
    padding: 1rem;
  }
  
  .glass-panel {
    padding: 1.5rem;
  }
  
  header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .logo {
    height: 32px;
  }
  
  .coordinates {
    text-align: left;
  }
  
  .form-options {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .flavor-header,
  .disclaimer {
    font-size: 0.6rem;
    padding: 0.3rem;
    letter-spacing: 0.5px;
  }

  .header-coordinates {
    display: none; /* Hide coordinates on mobile to save space */
  }
  
  .environment-status {
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.6rem;
    padding: 0.5rem;
  }
  
  .mission-note {
    font-size: 0.6rem;
    padding: 0.4rem;
    margin-top: 0.5rem;
  }
  
  .help-hint {
    font-size: 0.6rem;
    bottom: 0.5rem;
    padding: 0.3rem 0.5rem;
  }
  
  .time-label {
    font-size: 0.6rem;
  }
  
  .classified-stamp {
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
    right: 0.5rem;
    top: 0.5rem;
  }

  .auth-notification {
    width: 90%;
    font-size: 0.7rem;
    padding: 0.5rem;
  }

  .container {
    padding: 0.5rem;
  }
  
  .glass-panel {
    padding: 1rem;
  }
}

@media (max-width: 380px) {
  .flavor-header {
    flex-direction: column;
    gap: 0.2rem;
    align-items: flex-start;
  }
  
  .environment-status .status-item {
    font-size: 0.55rem;
  }
}

.coordinates {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--misty-gray);
  opacity: 0.6;
  text-align: right;
}

/* Warning State Styles */
body.warning-state .glass-panel {
  border-color: rgba(255, 193, 7, 0.1);
}

/* Danger State Styles - before full lockout */
body.danger-state .glass-panel:not(.lockout) {
  border-color: rgba(244, 67, 54, 0.1);
  box-shadow: 0 4px 30px rgba(244, 67, 54, 0.2), 0 0 50px rgba(244, 67, 54, 0.1), inset 0 0 15px rgba(244, 67, 54, 0.05);
}

/* State-specific particle colors */
body[data-security-level="warning"] .particle {
  background: var(--bioluminescent-teal) !important;
}

body[data-security-level="danger"] .particle {
  background: var(--bioluminescent-teal) !important;
}

/* State-specific scanner line */
body[data-security-level="warning"] .scanner-line {
  background: linear-gradient(90deg, transparent, var(--bioluminescent-teal), transparent);
}

body[data-security-level="danger"] .scanner-line {
  background: linear-gradient(90deg, transparent, var(--bioluminescent-teal), transparent);
  animation-duration: 1s; /* Faster scanning */
}

/* State-specific button colors */
body[data-security-level="warning"] .btn-primary {
  background: rgba(255, 193, 7, 0.2);
  color: var(--bioluminescent-teal);
  border: 1px solid rgba(255, 193, 7, 0.3);
}

body[data-security-level="danger"] .btn-primary {
  background: rgba(244, 67, 54, 0.2);
  color: var(--bioluminescent-teal);
  border: 1px solid rgba(244, 67, 54, 0.3);
}

/* Adjust input field colors by security state */
body[data-security-level="warning"] input:focus {
  border-color: var(--bioluminescent-teal);
}

body[data-security-level="danger"] input:focus {
  border-color: var(--bioluminescent-teal);
}

/* Flavor Header Styles - match EXACTLY with footer disclaimer text */
.flavor-header {
  font-family: var(--font-mono);
  display: flex;
  justify-content: space-between;
  width: 100%;
  padding: 0.5rem 0.2rem;
  margin-bottom: 1.5rem;
  font-size: 0.7rem; /* Match disclaimer size */
  letter-spacing: 1px; /* Match disclaimer letter spacing */
  text-transform: uppercase;
  opacity: 0.6; /* Match disclaimer opacity */
  transform: translateY(-10px);
  transition: opacity 0.8s, transform 0.8s;
}

.flavor-header.header-appeared {
  opacity: 0.6; /* Keep consistent with disclaimer */
  transform: translateY(0);
}

/* All flavor header elements match disclaimer color exactly */
.header-batch, .header-coordinates, .header-phrase {
  color: var(--misty-gray); /* Match footer disclaimer color */
}

.header-batch:before {
  content: ">";
  margin-right: 5px;
  color: var(--misty-gray); /* Match footer disclaimer color */
}

/* Flavor Header State Changes */
.header-batch.warning-alert {
  color: var(--evidence-amber);
}

.header-batch.warning-alert:before {
  content: "!>";
  color: var(--evidence-amber);
  animation: blink 1s infinite;
}

.header-batch.danger-alert {
  color: #f44336;
}

.header-batch.danger-alert:before {
  content: "!!>";
  color: #f44336;
  animation: blink 0.5s infinite;
}

.header-coordinates.warning-flicker {
  animation: coordinateFlicker 3s infinite;
  color: var(--evidence-amber);
}

.header-coordinates.danger-corrupt {
  animation: textGlitch 0.2s infinite;
  color: #f44336;
}

.header-phrase.danger-text {
  color: #f44336;
}

@keyframes coordinateFlicker {
  0%, 95%, 100% { opacity: 0.7; }
  97% { opacity: 0.3; }
  98% { opacity: 0.9; }
}

/* State changes for flavor header during breach */
html.total-breach .flavor-header {
  border-bottom-color: rgba(255, 0, 0, 0.3) !important;
}

html.total-breach .header-batch,
html.total-breach .header-coordinates,
html.total-breach .header-phrase {
  color: #ff6666 !important;
  text-shadow: 0 0 5px rgba(255, 0, 0, 0.7) !important;
}

html.total-breach .header-batch:before {
  content: "ERROR>";
  animation: blink 0.3s infinite !important;
}

/* Environment status panel */
.environment-status {
  display: flex;
  justify-content: space-between;
  font-family: var(--font-mono);
  font-size: 0.65rem;
  margin-top: 1.5rem;
  padding: 0.8rem;
  background-color: rgba(0, 0, 0, 0.2);
  border-top: 1px solid rgba(57, 240, 217, 0.1);
  border-radius: 4px;
}

.status-label {
  color: var(--misty-gray);
  margin-right: 0.5rem;
}

.status-value {
  font-weight: bold;
}

.status-ok {
  color: var(--bioluminescent-teal);
}

.status-warning {
  color: var(--evidence-amber);
  animation: blink 2s infinite;
}

/* Mission briefing note */
.mission-note {
  font-family: var(--font-mono);
  font-size: 0.65rem;
  margin-top: 1rem;
  padding: 0.5rem;
  background-color: rgba(255, 152, 0, 0.05);
  border-left: 2px solid var(--evidence-amber);
  color: var(--misty-gray);
  letter-spacing: 0.5px;
}

.mission-label {
  color: var(--evidence-amber);
  margin-right: 0.5rem;
}

/* Biometric Scanner Styles */
.biometric-scanner {
  display: none; /* Hidden by default */
  margin-top: 1.5rem;
}

.auth-divider {
  text-align: center;
  position: relative;
  margin: 1rem 0;
}

.auth-divider::before,
.auth-divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: calc(50% - 2rem);
  height: 1px;
  background: rgba(96, 125, 139, 0.2);
}

.auth-divider::before { left: 0; }
.auth-divider::after { right: 0; }

.auth-divider span {
  background: rgba(45, 48, 51, 0.4);
  padding: 0.5rem 1rem;
  color: var(--misty-gray);
  font-size: 0.8rem;
  letter-spacing: 2px;
}

.fingerprint-scanner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  cursor: pointer;
}

.scanner-pad {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(57, 240, 217, 0.05);
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(57, 240, 217, 0.2);
  transition: all 0.3s ease;
}

.scan-lines {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: repeating-linear-gradient(
    transparent,
    transparent 2px,
    rgba(57, 240, 217, 0.1) 2px,
    rgba(57, 240, 217, 0.1) 4px
  );
  opacity: 0;
  transition: opacity 0.3s;
}

.fingerprint-icon {
  position: absolute;
  inset: 20%;
  background: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"%3E%3Cpath fill="rgba(57, 240, 217, 0.3)" d="M12,2C6.477,2,2,6.477,2,12s4.477,10,10,10s10-4.477,10-10S17.523,2,12,2z M12,20c-4.418,0-8-3.582-8-8s3.582-8,8-8s8,3.582,8,8S16.418,20,12,20z M12,6c-3.314,0-6,2.686-6,6s2.686,6,6,6s6-2.686,6-6S15.314,6,12,6z M12,16c-2.209,0-4-1.791-4-4s1.791-4,4-4s4,1.791,4,4S14.209,16,12,16z"%3E%3C/path%3E%3C/svg%3E');
  opacity: 0.5;
}

.scanner-label {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--misty-gray);
  letter-spacing: 1px;
}

/* Scanner States */
.fingerprint-scanner.scanning .scan-lines {
  opacity: 1;
  animation: scanMove 2s linear infinite;
}

.fingerprint-scanner.scanning .scanner-pad {
  background: rgba(57, 240, 217, 0.1);
  box-shadow: 0 0 15px rgba(57, 240, 217, 0.2);
}

.fingerprint-scanner.processing .scanner-pad {
  background: rgba(255, 152, 0, 0.1);
  border-color: rgba(255, 152, 0, 0.2);
  animation: pulse 1s infinite;
}

.fingerprint-scanner.scan-error .scanner-pad {
  background: rgba(244, 67, 54, 0.1);
  border-color: rgba(244, 67, 54, 0.2);
  box-shadow: 0 0 15px rgba(244, 67, 54, 0.2);
}

.fingerprint-scanner.scan-error[data-security-level="normal"] .scanner-pad {
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 15px var(--bioluminescent-teal);
}

.fingerprint-scanner.scan-error[data-security-level="warning"] .scanner-pad {
  border-color: var(--evidence-amber);
  box-shadow: 0 0 15px var(--evidence-amber);
}

.fingerprint-scanner.scan-error[data-security-level="danger"] .scanner-pad {
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 20px var(--bioluminescent-teal);
}

.fingerprint-scanner.scan-error[data-security-level="normal"] .scanner-pad {
  background: rgba(244, 67, 54, 0.1);
  border-color: rgba(244, 67, 54, 0.2);
  box-shadow: 0 0 15px rgba(244, 67, 54, 0.2);
  animation: scanPulseNormal 2s infinite;
}

.fingerprint-scanner.scan-error[data-security-level="warning"] .scanner-pad {
  background: rgba(255, 193, 7, 0.1);
  border-color: rgba(255, 193, 7, 0.2);
  box-shadow: 0 0 15px rgba(255, 193, 7, 0.2);
  animation: scanPulseWarning 1.5s infinite;
}

.fingerprint-scanner.scan-error[data-security-level="danger"] .scanner-pad {
  background: rgba(255, 0, 0, 0.15);
  border-color: rgba(255, 0, 0, 0.3);
  box-shadow: 0 0 20px rgba(255, 0, 0, 0.3);
  animation: scanPulseDanger 0.8s infinite;
}

@keyframes scanPulseNormal {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.05); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes scanPulseWarning {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.7; }
  100% { transform: scale(1); opacity: 1; }
}

@keyframes scanPulseDanger {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.15); opacity: 0.6; }
  100% { transform: scale(1); opacity: 1; }
}

/* Show scanner only on mobile */
@media (max-width: 768px) {
  .biometric-scanner.mobile-only {
    display: block;
  }
}

/* Move credentials link below form elements */
.form-footer {
  margin-top: 1rem;
  text-align: center;
  font-size: 0.85rem;
}

/* Thermal hint styling */
.thermal-hints {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 1000;
}

.thermal-hint {
  position: absolute;
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--bioluminescent-teal);
  padding: 0.5rem;
  border-radius: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
  background: rgba(57, 240, 217, 0.05);
  border: 1px solid rgba(57, 240, 217, 0.1);
  pointer-events: none;
}

/* Show hints when mouse (thermal scanner) is nearby */
.thermal-hint:hover,
.thermal-hint:hover ~ .thermal-cursor {
  opacity: 0.8;
}

/* Enhance thermal cursor for hint detection */
.thermal-cursor:hover ~ .thermal-hints .thermal-hint {
  opacity: 0.8;
  animation: hintGlow 2s infinite;
}

@keyframes hintGlow {
  0%, 100% { box-shadow: 0 0 10px rgba(57, 240, 217, 0.2); }
  50% { box-shadow: 0 0 20px rgba(57, 240, 217, 0.4); }
}

.auth-success .glass-panel {
  border-color: var(--bioluminescent-teal);
  box-shadow: 
    0 4px 30px rgba(57, 240, 217, 0.3),
    0 0 50px rgba(57, 240, 217, 0.2),
    inset 0 0 15px rgba(57, 240, 217, 0.1);
  animation: successPanel 3s forwards;
}

@keyframes successPulse {
  0%, 100% { opacity: 0.8; }
  50% { opacity: 1; }
}

@keyframes successPanel {
  0% { transform: scale(1); }
  50% { transform: scale(1.02); }
  100% { transform: scale(1.5); opacity: 0; }
}

/* Add error flash animation */
.error-flash {
  animation: errorPulse 0.6s;
}

@keyframes errorPulse {
  0%, 100% { box-shadow: none; }
  50% { box-shadow: 0 0 8px #f44336; }
}

/* Aptitude Test Styles */
.aptitude-test-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 0;
  transition: opacity 0.5s ease;
  pointer-events: none;
}

.aptitude-test-container.hidden {
  display: none !important;
}

.aptitude-test-container.active {
  opacity: 1;
  pointer-events: all;
}

.aptitude-panel {
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  z-index: 10000;
}

.aptitude-test-container.active::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background-color: var(--bioluminescent-teal);
  z-index: 10001;
  animation: pulse 1.5s infinite;
}

/* Aptitude Test Styles */
.aptitude-test-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999; /* Ensure it's above everything */
  pointer-events: none; /* Initially no interaction */
}

.aptitude-test-container.hidden {
  display: none !important;
}

.aptitude-test-container.active {
  opacity: 1;
  pointer-events: all; /* Enable interaction when active */
}

.test-section {
  display: none;
}

.test-section.active {
  display: block;
}

/* Test Header */
.test-header {
  margin-bottom: 2rem;
}

.test-header h2 {
  font-family: var(--font-heading);
  color: var(--bioluminescent-teal);
  text-align: center;
  font-size: 1.8rem;
  margin-bottom: 1rem;
  letter-spacing: 1px;
}

/* Test Progress Bar */
.test-progress {
  margin-bottom: 2rem;
}

.progress-bar {
  height: 6px;
  background: rgba(45, 48, 51, 0.6);
  border-radius: 3px;
  margin-bottom: 0.5rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  width: 33.33%;
  background: var(--bioluminescent-teal);
  box-shadow: 0 0 10px var(--bioluminescent-teal);
  transition: width 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.progress-label {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--misty-gray);
  text-align: right;
}

/* Test Sections */
.test-section {
  display: none;
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}

.test-section.active {
  display: block;
  animation: fadeInUp 0.5s forwards;
}

.test-section h3 {
  font-family: var(--font-heading);
  color: #fff;
  font-size: 1.2rem;
  margin-bottom: 1rem;
  letter-spacing: 0.5px;
}

.test-instruction {
  font-family: var(--font-body);
  color: var(--misty-gray);
  margin-bottom: 2rem;
  font-size: 0.9rem;
  padding: 0.75rem;
  border: 1px solid rgba(57, 240, 217, 0.1);
  background: rgba(57, 240, 217, 0.05);
  border-radius: 4px;
}

/* Pattern Recognition Test */
.pattern-sequence {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 2rem 0;
  background: rgba(45, 48, 51, 0.4);
  padding: 1.5rem;
  border-radius: 6px;
  border: 1px solid rgba(57, 240, 217, 0.15);
}

.pattern-item {
  font-size: 2.5rem;
  color: var(--bioluminescent-teal);
  text-align: center;
  min-width: 60px;
  animation: subtle-glow 2s infinite alternate;
  font-family: var(--font-mono);
}

.pattern-question {
  font-size: 3rem;
  color: var(--evidence-amber);
  animation: blink 1.5s infinite;
  font-weight: bold;
}

.pattern-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-top: 2rem;
}

.pattern-option {
  padding: 1rem;
  font-size: 2rem;
  color: var(--bioluminescent-teal);
  background: rgba(45, 48, 51, 0.4);
  border: 1px solid rgba(57, 240, 217, 0.15);
  border-radius: 6px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pattern-option:hover {
  background: rgba(57, 240, 217, 0.05);
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 20px rgba(57, 240, 217, 0.2);
}

.pattern-option.selected {
  background: rgba(57, 240, 217, 0.1);
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 25px rgba(57, 240, 217, 0.25);
}

/* Memory Test */
.memory-map {
  position: relative;
  width: 100%;
  padding-top: 100%;
  margin: 2rem 0;
  background: rgba(45, 48, 51, 0.4);
  border: 1px solid rgba(57, 240, 217, 0.15);
  border-radius: 6px;
  overflow: hidden;
}

.map-grid {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-template-rows: repeat(4, 1fr);
  gap: 1px;
  background: rgba(57, 240, 217, 0.1);
}

.map-grid::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: 
    linear-gradient(rgba(57, 240, 217, 0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(57, 240, 217, 0.1) 1px, transparent 1px);
  background-size: calc(100% / 4) calc(100% / 4);
  z-index: 1;
}

.map-marker {
  position: absolute;
  width: 20px;
  height: 20px;
  background: var(--evidence-amber);
  border-radius: 50%;
  box-shadow: 0 0 10px var(--evidence-amber);
  transform: translate(-50%, -50%);
  z-index: 2;
  animation: pulse 1.5s infinite;
}

.map-marker[data-location="A3"] {
  top: 12.5%;
  left: 62.5%;
}

.map-marker[data-location="C1"] {
  top: 62.5%;
  left: 12.5%;
}

.map-marker[data-location="D4"] {
  top: 87.5%;
  left: 87.5%;
}

.map-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(45, 48, 51, 0.95);
  z-index: 10;
  opacity: 0;
  transition: opacity 0.5s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-mono);
  font-size: 2rem;
  color: var(--bioluminescent-teal);
}

.map-overlay.visible {
  opacity: 1;
}

.memory-recall {
  margin-top: 1rem;
}

.memory-recall.hidden {
  display: none;
}

.memory-recall p {
  margin-bottom: 1rem;
  color: var(--misty-gray);
  font-family: var(--font-body);
}

.recall-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}

.recall-cell {
  aspect-ratio: 1;
  background: rgba(45, 48, 51, 0.6);
  border: 1px solid rgba(57, 240, 217, 0.1);
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.recall-cell:hover {
  background: rgba(57, 240, 217, 0.05);
  border-color: rgba(57, 240, 217, 0.3);
}

.recall-cell.selected {
  background: rgba(57, 240, 217, 0.1);
  border-color: var(--bioluminescent-teal);
  box-shadow: 0 0 10px rgba(57, 240, 217, 0.2);
}

.recall-cell.selected::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 10px;
  height: 10px;
  background: var(--bioluminescent-teal);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 5px var(--bioluminescent-teal);
}

/* Reaction Test */
.reaction-arena {
  width: 100%;
  height: 300px;
  position: relative;
  margin: 2rem 0;
  background: rgba(45, 48, 51, 0.4);
  border: 1px solid rgba(57, 240, 217, 0.15);
  border-radius: 6px;
  overflow: hidden;
}

.capture-counter {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 0.5rem 0.75rem;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(57, 240, 217, 0.2);
  border-radius: 4px;
  font-family: var(--font-mono);
  font-size: 0.8rem;
  color: var(--bioluminescent-teal);
  z-index: 20;
}

.cryptid-target {
  position: absolute;
  width: 40px;
  height: 30px;
  background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 30"><path d="M10,20 C15,5 25,5 30,20 C25,12 15,12 10,20 Z" fill="%2339F0D9" fill-opacity="0.6" /></svg>');
  cursor: pointer;
  transform-origin: center;
  transition: all 0.2s ease;
  opacity: 0;
  z-index: 10;
}

.cryptid-target.visible {
  opacity: 1;
  animation: fadeInTarget 0.3s ease-out;
}

.cryptid-target.captured {
  transform: scale(1.5);
  opacity: 0;
  animation: captureEffect 0.5s ease-out;
}

/* Results Section */
.results-section {
  text-align: center;
}

.results-panel {
  background: rgba(45, 48, 51, 0.4);
  border: 1px solid rgba(57, 240, 217, 0.2);
  border-radius: 6px;
  padding: 3rem 2rem;
  margin: 2rem 0;
  position: relative;
  overflow: hidden;
}

.result-icon {
  font-size: 4rem;
  color: var(--bioluminescent-teal);
  margin-bottom: 1rem;
  text-shadow: 0 0 20px var(--bioluminescent-teal);
  animation: successPulse 2s infinite;
}

.results-panel h4 {
  font-family: var(--font-heading);
  color: #fff;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  letter-spacing: 1px;
}

.security-clearance {
  display: inline-block;
  margin-top: 1.5rem;
  padding: 0.5rem 1rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid var(--bioluminescent-teal);
  border-radius: 4px;
}

.clearance-label {
  font-family: var(--font-mono);
  font-size: 0.8rem;
  color: var(--misty-gray);
  margin-right: 0.5rem;
}

.clearance-value {
  font-family: var(--font-mono);
  font-size: 1.2rem;
  color: var(--bioluminescent-teal);
  font-weight: bold;
  animation: glow 1.5s infinite alternate;
}

.scan-effect {
  position: absolute;
  top: 0;
  left: -100%;
  width: 200%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(57, 240, 217, 0.1), transparent);
  animation: scanEffect 2s linear infinite;
  z-index: 1;
  pointer-events: none;
}

.completion-message {
  color: var(--misty-gray);
  line-height: 1.7;
  margin-top: 2rem;
  font-family: var(--font-body);
}

.completion-message p:last-child {
  margin-top: 1rem;
  color: var(--bioluminescent-teal);
  animation: pulse 2s infinite;
}

/* Test Navigation */
.test-navigation {
  margin-top: 1.5rem;
  text-align: center;
}

/* Animations */
@keyframes subtle-glow {
  0% { opacity: 0.7; }
  100% { opacity: 1; }
}

@keyframes scanEffect {
  0% { transform: translateX(0); }
  100% { transform: translateX(50%); }
}

@keyframes fadeInTarget {
  0% { opacity: 0; transform: scale(0.5); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes captureEffect {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.2); box-shadow: 0 0 20px var(--bioluminescent-teal); }
  100% { opacity: 0; transform: scale(1.5); }
}

@keyframes glow {
  0% { text-shadow: 0 0 5px var(--bioluminescent-teal); }
  100% { text-shadow: 0 0 15px var(--bioluminescent-teal); }
}
</style>
</head>
<body>
  <div class="background">
    <div class="noise"></div>
    <div class="gradient-overlay"></div>
  </div>
  
  <div class="topographic-overlay"></div>
  
  <div class="container">
    <header>
      <img src="logo.svg" alt="Cryptid Collective" class="logo">
      <div class="coordinates">
        <span id="coordinates">47°10′N 52°44′W</span>
        <span id="timestamp">2023-11-07 20:15:33 UTC</span>
      </div>
    </header>

    <main class="glass-panel">
      <div class="lockout-message">SECURITY PROTOCOL ACTIVATED<span>Please reload page to continue</span></div>
      
      <div class="classified-stamp">Level 3 Clearance</div>
      <div class="morse-code">... . -.-. ..- .-. .. - -.--  .--. .-. --- - --- -.-. --- .-..</div>
      <div class="hidden-cryptid"></div>
      <div class="security-grid"></div>
      <div class="binary-code">01010011 01000101 01000011 01010101 01010010 01000101</div>
      <div class="scan-animation"></div>
      
      <div class="form-container" id="sign-in-container">
        <h1 data-text="Field Researcher Authentication">Field Researcher Authentication</h1>
        <div class="classified-stripe">
          CLEARANCE REQUIRED
        </div>
        
        <form id="sign-in-form">
          <div class="form-group">
            <label for="researcher-id">Researcher ID</label>
            <div class="input-container">
              <input type="email" id="researcher-id" required>
              <div class="input-glow"></div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="clearance-code">Clearance Code</label>
            <div class="input-container">
              <input type="password" id="clearance-code" required>
              <div class="input-glow"></div>
            </div>
          </div>
          
          <div class="form-options">
            <div class="custom-toggle-container" id="stealth-login-container">
              <div class="custom-toggle" id="stealth-login-toggle"></div>
              <input type="hidden" name="stealth-login" value="false">
              <span>Enable Stealth Login</span>
            </div>
            <a href="#" class="accent-link">Access Code Recovery</a>
          </div>
          
          <button type="submit" class="btn-primary" id="authenticate-btn">
            AUTHENTICATE
            <span class="btn-glow"></span>
            <div class="security-indicators">
              <div class="indicator"></div>
              <div class="indicator"></div>
              <div class="indicator"></div>
            </div>
          </button>
          
          <div class="biometric-scanner mobile-only">
            <!-- ...scanner code... -->
          </div>
          
          <div class="form-footer">
            <p>No field credentials? <a href="#" id="show-register" class="accent-link">Request Access</a></p>
          </div>
        </form>
      </div>
      
      <div class="form-container hidden" id="register-container">
        <h1 data-text="Field Researcher Onboarding">Field Researcher Onboarding</h1>
        <div class="classified-stripe">NEW PERSONNEL REGISTRATION</div>
        
        <form id="register-form">
          <div class="form-group">
            <label for="new-researcher-name">Operative Name</label>
            <div class="input-container">
              <span class="input-prefix">&gt;</span>
              <input type="text" id="new-researcher-name" placeholder="SURNAME, Given Names" required>
              <div class="input-glow"></div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="new-researcher-id">Field Contact</label>
            <div class="input-container">
              <span class="input-prefix">#</span>
              <input type="email" id="new-researcher-id" placeholder="secure email required" required>
              <div class="input-glow"></div>
            </div>
          </div>

          <div class="form-group">
            <label for="clearance-code-new">Create Clearance Code</label>
            <div class="input-container">
              <span class="input-prefix secure">#</span>
              <input type="password" 
                     id="clearance-code-new" 
                     placeholder="MIN 8 CHARS, CAPS & NUMBERS"
                     required>
              <div class="input-glow"></div>
            </div>
          </div>

          <div class="protocol-acceptance">
            <div class="custom-toggle-container" id="protocol-checkbox-container">
              <div class="custom-toggle" id="protocol-checkbox-toggle"></div>
              <input type="hidden" name="protocol-accepted" value="false">
              <span>I acknowledge the <span class="accent-text">Cryptid Collective Field Protocol</span> and accept the risks of paranormal research</span>
            </div>
          </div>
          
          <button type="submit" class="btn-primary">
            <span class="btn-text">INITIALIZE CLEARANCE</span>
            <span class="btn-glow"></span>
          </button>
          
          <div class="form-footer">
            <p>Already have access? <a href="#" id="show-login" class="accent-link">AUTHENTICATE</a></p>
          </div>
        </form>
      </div>
    </main>
    
    <footer>
      <p class="disclaimer">CRYPTID COLLECTIVE · CONFIDENTIAL · FOR APPROVED RESEARCHERS ONLY</p>
    </footer>
  </div>
  
  <div class="thermal-cursor"></div>
  
  <!-- Aptitude Test Overlay -->
  <div class="aptitude-test-container hidden">
    <div class="glass-panel aptitude-panel">
      <div class="classified-stamp">RESEARCHER EVALUATION</div>
      <div class="test-header">
        <h2>FIELD APTITUDE EVALUATION</h2>
        <div class="classified-stripe">CLEARANCE VERIFICATION IN PROGRESS</div>
      </div>
      
      <div class="test-progress">
        <div class="progress-bar">
          <div class="progress-fill"></div>
        </div>
        <div class="progress-label">TEST 1/3</div>
      </div>
      
      <!-- Test 1: Pattern Recognition -->
      <div class="test-section active" data-test="1">
        <h3>CRYPTID PATTERN RECOGNITION</h3>
        <p class="test-instruction">Identify the next symbol in the sequence to verify cognitive mapping skills.</p>
        
        <div class="pattern-sequence">
          <div class="pattern-item">≈</div>
          <div class="pattern-item">≋</div>
          <div class="pattern-item">≈≈</div>
          <div class="pattern-item">≋≋</div>
          <div class="pattern-item pattern-question">?</div>
        </div>
        
        <div class="pattern-options">
          <div class="pattern-option" data-value="≈≈≈">≈≈≈</div>
          <div class="pattern-option" data-value="≋≋≋">≋≋≋</div>
          <div class="pattern-option correct" data-value="≈≈≋">≈≈≋</div>
          <div class="pattern-option" data-value="≋≈≋">≋≈≋</div>
        </div>
      </div>
      
      <!-- Test 2: Memory Test -->
      <div class="test-section" data-test="2">
        <h3>CRYPTID SIGHTING MEMORY</h3>
        <p class="test-instruction">Memorize the locations shown. The map will clear in <span class="countdown">5</span> seconds.</p>
        
        <div class="memory-map">
          <div class="map-grid">
            <div class="map-marker" data-location="A3"></div>
            <div class="map-marker" data-location="C1"></div>
            <div class="map-marker" data-location="D4"></div>
          </div>
          <div class="map-overlay"></div>
        </div>
        
        <div class="memory-recall hidden">
          <p>Select all locations where sightings occurred:</p>
          <div class="recall-grid">
            <div class="recall-cell" data-location="A1"></div>
            <div class="recall-cell" data-location="A2"></div>
            <div class="recall-cell" data-location="A3"></div>
            <div class="recall-cell" data-location="A4"></div>
            <div class="recall-cell" data-location="B1"></div>
            <div class="recall-cell" data-location="B2"></div>
            <div class="recall-cell" data-location="B3"></div>
            <div class="recall-cell" data-location="B4"></div>
            <div class="recall-cell" data-location="C1"></div>
            <div class="recall-cell" data-location="C2"></div>
            <div class="recall-cell" data-location="C3"></div>
            <div class="recall-cell" data-location="C4"></div>
            <div class="recall-cell" data-location="D1"></div>
            <div class="recall-cell" data-location="D2"></div>
            <div class="recall-cell" data-location="D3"></div>
            <div class="recall-cell" data-location="D4"></div>
          </div>
        </div>
      </div>
      
      <!-- Test 3: Reaction Test -->
      <div class="test-section" data-test="3">
        <h3>FIELD REACTION ASSESSMENT</h3>
        <p class="test-instruction">Capture the cryptid silhouettes by clicking on them before they disappear. Minimum 4 captures required.</p>
        
        <div class="reaction-arena">
          <div class="capture-counter">CAPTURES: <span>0</span>/4</div>
        </div>
      </div>
      
      <!-- Results Section -->
      <div class="test-section results-section" data-test="results">
        <h3>FIELD RESEARCHER INITIALIZATION</h3>
        <div class="results-panel">
          <div class="scanner-line"></div>
          <div class="result-icon">✓</div>
          <h4>APTITUDE VERIFIED</h4>
          <p>Your field researcher profile has been initialized.</p>
          <div class="security-clearance">
            <span class="clearance-label">CLEARANCE LEVEL:</span>
            <span class="clearance-value">2</span>
          </div>
          <div class="scan-effect"></div>
        </div>
        
        <div class="completion-message">
          <p>Your credentials have been established.</p>
          <p>Returning to authentication interface...</p>
        </div>
      </div>
      
      <div class="test-navigation">
        <button type="button" class="btn-primary test-continue">
          <span class="btn-text">BEGIN ASSESSMENT</span>
          <span class="btn-glow"></span>
        </button>
      </div>
    </div>
  </div>
  
  <script>
    // Debug helper to make sure aptitude tests can be triggered manually
    window.debugStartTests = function() {
      console.log('Manual test trigger');
      if (typeof startAptitudeTests === 'function') {
        startAptitudeTests();
      } else {
        console.error('startAptitudeTests function not found');
      }
    };

    // Debug function to manually trigger aptitude tests
    window.manuallyStartAptitudeTests = function() {
      console.log('Manual test trigger');
      if (typeof startAptitudeTests === 'function') {
        startAptitudeTests();
      } else {
        console.error('startAptitudeTests function not found');
        alert('Error: Test system not available');
      }
    };

    // Debug function that can be called from the console if needed
    window.debugAptitudeTests = function() {
      console.log("Manual debug trigger for aptitude tests");
      if (typeof launchAptitudeTests === 'function') {
        launchAptitudeTests();
      } else {
        console.error("Launch function not available");
        
        // Emergency direct manipulation
        const container = document.querySelector('.aptitude-test-container');
        if (container) {
          container.style.display = 'flex';
          container.classList.remove('hidden');
          container.classList.add('active');
          console.log("Container manually activated");
        } else {
          console.error("Container not found");
      }
    };
  </script>
  <script src="form-elements.js"></script>
  <script > // DOM elements
const signInContainer = document.getElementById('sign-in-container');
const registerContainer = document.getElementById('register-container');
const showRegisterLink = document.getElementById('show-register');
const showLoginLink = document.getElementById('show-login');
const step1 = document.getElementById('step-1');
const step2 = document.getElementById('step-2');
const toStep2Button = document.getElementById('to-step-2');
const backToStep1Button = document.getElementById('back-to-step-1');
const timestampElement = document.getElementById('timestamp');
const inputFields = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');

// Mouse tracking
document.addEventListener('mousemove', (e) => {
  const x = e.clientX / window.innerWidth * 100;
  const y = e.clientY / window.innerHeight * 100;
  document.documentElement.style.setProperty('--mouse-x', `${x}%`);
  document.documentElement.style.setProperty('--mouse-y', `${y}%`);
});

// Toggle between sign in and registration forms
showRegisterLink.addEventListener('click', (e) => {
  e.preventDefault();
  signInContainer.classList.add('hidden');
  registerContainer.classList.remove('hidden');
});

showLoginLink.addEventListener('click', (e) => {
  e.preventDefault();
  registerContainer.classList.add('hidden');
  signInContainer.classList.remove('hidden');
});

// Remove step navigation handlers
document.getElementById('to-step-2')?.remove();
document.getElementById('to-step-3')?.remove();
document.getElementById('back-to-step-1')?.remove();
document.getElementById('back-to-step-2')?.remove();

// Form submissions
document.getElementById('sign-in-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const researcherId = document.getElementById('researcher-id').value;
  const clearanceCode = document.getElementById('clearance-code').value;
  
  if (researcherId === SECRET_CREDENTIALS.id && clearanceCode === SECRET_CREDENTIALS.code) {
    // Success state
    showAuthSuccess();
    return;
  }
  
  // Existing failure code
  loginAttemptsHandler.attempt();
});

// Fix registration form submission to properly display aptitude tests
document.addEventListener('DOMContentLoaded', function() {
  console.log("DOM loaded, setting up form handlers");
  
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    console.log("Register form found");
    
    // Clone and replace to remove existing event listeners
    const oldForm = registerForm;
    const newForm = oldForm.cloneNode(true);
    oldForm.parentNode.replaceChild(newForm, oldForm);
    
    newForm.addEventListener('submit', function(e) {
      console.log("Register form submitted");
      e.preventDefault();
      
      // Validate the form
      const nameInput = document.getElementById('new-researcher-name');
      const emailInput = document.getElementById('new-researcher-id');
      const passwordInput = document.getElementById('clearance-code-new');
      const protocolCheckbox = document.querySelector('.protocol-acceptance input[type="checkbox"]');
      
      let isValid = true;
      
      if (!nameInput || !nameInput.value.trim()) {
        if (nameInput) flashInputError(nameInput);
        isValid = false;
      }
      
      if (!emailInput || !emailInput.value.trim()) {
        if (emailInput) flashInputError(emailInput);
        isValid = false;
      }
      
      if (!passwordInput || !passwordInput.value.trim()) {
        if (passwordInput) flashInputError(passwordInput);
        isValid = false;
      }
      
      if (!protocolCheckbox || !protocolCheckbox.checked) {
        if (protocolCheckbox && protocolCheckbox.parentElement) {
          protocolCheckbox.parentElement.classList.add('error-flash');
          setTimeout(() => {
            protocolCheckbox.parentElement.classList.remove('error-flash');
          }, 1000);
        }
        isValid = false;
      }
      
      if (!isValid) {
        console.log("Form validation failed");
        return;
      }
      
      console.log("Form is valid, launching aptitude tests");
      launchAptitudeTests();
    });
    
    // Also add direct click handler to the submit button for extra reliability
    const initButton = newForm.querySelector('button[type="submit"]');
    if (initButton) {
      console.log("Init button found");
      initButton.addEventListener('click', function(e) {
        console.log("Init button clicked");
        // The form's submit event will handle this
      });
    }
  }
});

// Create a new, simplified function to ensure aptitude tests are displayed
function launchAptitudeTests() {
  console.log("Launching aptitude tests");
  
  const aptitudeContainer = document.querySelector('.aptitude-test-container');
  if (!aptitudeContainer) {
    console.error("Aptitude test container not found!");
    // Create a fallback container if it doesn't exist
    createFallbackAptitudeContainer();
    return;
  }
  
  // Make sure it's visible in the DOM
  aptitudeContainer.style.display = 'flex';
  aptitudeContainer.classList.remove('hidden');
  
  // Force browser reflow
  void aptitudeContainer.offsetWidth;
  
  // Add active class for animations and visibility
  aptitudeContainer.classList.add('active');
  
  console.log("Aptitude container activated");
  
  // Initialize test functionality if available
  if (typeof initializeCurrentTest === 'function') {
    setTimeout(() => {
      try {
        console.log("Initializing first test");
        initializeCurrentTest(1);
        
        if (typeof setupTestNavigation === 'function') {
          setupTestNavigation();
        }
      } catch (err) {
        console.error("Error initializing tests:", err);
      }
    }, 100);
  } else {
    console.error("Test initialization function not found");
  }
}

// Update timestamp every second
function updateTimestamp() {
  const now = new Date();
  const options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  };
  
  const formattedDate = now.toLocaleString('en-US', options)
    .replace(',', '')
    .replace(/(\d+)\/(\d+)\/(\d+)/, '$3-$1-$2') + ' UTC';
  
  timestampElement.textContent = formattedDate;
}

// Initialize timestamp and update it every second
updateTimestamp();
setInterval(updateTimestamp, 1000);

// Input focus effects
inputFields.forEach(input => {
  input.addEventListener('focus', () => {
    const container = input.closest('.glass-panel');
    container.style.transform = 'scale(1.02) translateZ(20px)';
    playGlitchEffect(container);
  });
  
  input.addEventListener('blur', () => {
    const container = input.closest('.glass-panel');
    container.style.transform = '';
  });
});

// Helper functions
function flashInputError(inputElement) {
  inputElement.classList.add('error');
  inputElement.parentElement.querySelector('.input-glow').style.boxShadow = `0 0 var(--glow-strength) #f44336`;
  inputElement.parentElement.querySelector('.input-glow').style.opacity = '0.5';
  
  setTimeout(() => {
    inputElement.classList.remove('error');
    inputElement.parentElement.querySelector('.input-glow').style.boxShadow = '';
    inputElement.parentElement.querySelector('.input-glow').style.opacity = '';
  }, 1000);
}

// Fix the recursive function call that's causing a stack overflow
function playGlitchEffect(element) {
  const glitchLines = 3;
  const glitches = [];
  
  for (let i = 0; i < glitchLines; i++) {
    const glitch = document.createElement('div');
    glitch.className = 'glitch-line';
    glitch.style.top = `${Math.random() * 100}%`;
    glitch.style.left = `-10%`;
    glitch.style.width = '120%';
    glitch.style.height = '1px';
    glitch.style.background = `rgba(57, 240, 217, ${Math.random() * 0.5})`;
    glitch.style.transform = `translateY(${Math.random() * 10 - 5}px)`;
    element.appendChild(glitch);
    glitches.push(glitch);
  }
  
  setTimeout(() => {
    glitches.forEach(g => g.remove());
  }, 300);
}

// Modified to only show failure or processing (no success message)
function simulateFormSubmission(loadingMessage, successMessage) {
  // Function kept for compatibility but not used for authentication
  console.log(loadingMessage);
  // No overlay displayed anymore
}

// Add DNA particle system
function createParticleField() {
  const field = document.createElement('div');
  field.className = 'particle-field';
  document.body.appendChild(field);

  for (let i = 0; i < 50; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = `${Math.random() * 100}%`;
    particle.style.animationDelay = `${Math.random() * 20}s`;
    particle.style.opacity = Math.random() * 0.5 + 0.2;
    field.appendChild(particle);
  }
}

// Add scanner effect
function addScannerLine() {
  const scanner = document.createElement('div');
  scanner.className = 'scanner-line';
  document.querySelector('.glass-panel').appendChild(scanner);
}

// Add radar ping on click
document.addEventListener('click', (e) => {
  const ping = document.createElement('div');
  ping.className = 'radar-ping';
  ping.style.left = `${e.pageX}px`;
  ping.style.top = `${e.pageY}px`;
  document.body.appendChild(ping);
  
  setTimeout(() => ping.remove(), 2000);
});

// Glitch text effect
function glitchText(element) {
  element.classList.add('glitch-text');
  element.setAttribute('data-text', element.textContent);
  
  setTimeout(() => {
    element.classList.remove('glitch-text');
  }, 2000);
}

// Terminal typing effect
function typeText(element, text, speed = 50) {
  let index = 0;
  element.textContent = '';
  
  function type() {
    if (index < text.length) {
      element.textContent += text.charAt(index);
      index++;
      setTimeout(type, speed);
    }
  }
  
  type();
}

// Add topographic map overlay if missing
function addTopographicOverlay() {
  if (!document.querySelector('.topographic-overlay')) {
    const topo = document.createElement('div');
    topo.className = 'topographic-overlay';
    document.body.appendChild(topo);
  }
}

// Add classified stamp effect
function addClassifiedStamp() {
  if (!document.querySelector('.classified-stamp')) {
    const stamp = document.createElement('div');
    stamp.className = 'classified-stamp';
    stamp.textContent = 'LEVEL 4 CLEARANCE REQUIRED';
    document.querySelector('.glass-panel').appendChild(stamp);
  }
}

// Add flavor header function
function addFlavorHeader() {
  if (document.querySelector('.flavor-header')) return; // Don't create duplicate

  // Match disclaimer styling exactly
  const disclaimer = document.querySelector('.disclaimer');
  
  // Create the flavor header container
  const headerContainer = document.createElement('div');
  headerContainer.className = 'flavor-header';
  
  // Create the three components with styling matching disclaimer
  const batchFile = document.createElement('div');
  batchFile.className = 'header-batch';
  batchFile.textContent = 'CRYPTID_COLLECTIVE.SYS';
  
  // Generate semi-random coordinates
  const coordinates = document.createElement('div');
  coordinates.className = 'header-coordinates';
  const lat = (Math.random() * 75 * (Math.random() > 0.5 ? 1 : -1)).toFixed(6);
  const long = (Math.random() * 150 * (Math.random() > 0.5 ? 1 : -1)).toFixed(6);
  const alt = Math.floor(Math.random() * 2500);
  coordinates.textContent = `[${lat}, ${long}, ${alt}m]`;
  coordinates.setAttribute('data-original', coordinates.textContent);
  
  const latinPhrases = [
    "VIGILIA AETERNUS",
    "CRYPTOS REVELATIO",
    "VERITAS OCCULTA",
    "CUSTODES MYSTERIA",
    "INVENIT INCOGNITA",
    "MONSTRUM VESTIGIUM",
    "SCIENTIA ARCANA"
  ];
  const phrase = document.createElement('div');
  phrase.className = 'header-phrase';
  phrase.textContent = latinPhrases[Math.floor(Math.random() * latinPhrases.length)];
  
  // Assemble the header
  headerContainer.appendChild(batchFile);
  headerContainer.appendChild(coordinates);
  headerContainer.appendChild(phrase);
  
  // Add to the DOM - place at the top of the container
  const container = document.querySelector('.container');
  container.insertBefore(headerContainer, container.firstChild);
  
  // Add animation effect - terminal typing style
  setTimeout(() => {
    headerContainer.classList.add('header-appeared');
  }, 300);
}

// Function to glitch coordinates during security events
function glitchCoordinates(level) {
  const coordinates = document.querySelector('.header-coordinates');
  if (!coordinates) return;
  
  const originalText = coordinates.getAttribute('data-original');
  
  if (level === 'warning') {
    // Warning level - coordinates become unstable
    coordinates.classList.add('warning-flicker');
    
    // Change coordinates occasionally
    const glitchInterval = setInterval(() => {
      if (!document.body.classList.contains('security-breach')) {
        const glitchChance = Math.random();
        if (glitchChance > 0.7) {
          const lat = (Math.random() * 90 * (Math.random() > 0.5 ? 1 : -1)).toFixed(6);
          const long = (Math.random() * 180 * (Math.random() > 0.5 ? 1 : -1)).toFixed(6);
          const alt = Math.floor(Math.random() * 9000);
          coordinates.textContent = `[${lat}, ${long}, ${alt}m]`;
        } else {
          coordinates.textContent = originalText;
        }
      } else {
        clearInterval(glitchInterval);
      }
    }, 2000);
  } 
  else if (level === 'danger') {
    // Danger level - coordinates are corrupted with symbols
    coordinates.classList.remove('warning-flicker');
    coordinates.classList.add('danger-corrupt');
    coordinates.textContent = '[ERROR://COORD_OVERFLOW]';
  }
  else {
    // Reset
    coordinates.classList.remove('warning-flicker', 'danger-corrupt');
    coordinates.textContent = originalText;
  }
}

// Add the shine effect to the glass panel
function addShineEffect() {
  const glassPanels = document.querySelectorAll('.glass-panel');
  
  glassPanels.forEach(panel => {
    // Create shine effect element if it doesn't exist
    if (!panel.querySelector('.shine-effect')) {
      const shineEffect = document.createElement('div');
      shineEffect.className = 'shine-effect';
      panel.appendChild(shineEffect);
    }
  });
}

// Add terminal-style input prefixes and enhanced placeholders
function enhanceFormExperience() {
  // Add command-line style prefixes to inputs
  document.querySelectorAll('.form-group').forEach(group => {
    const inputEl = group.querySelector('input');
    const label = group.querySelector('label');
    
    if (inputEl && label) {
      // Add field prefix based on input type
      if (inputEl.id === 'researcher-id') {
        inputEl.setAttribute('placeholder', 'format: CC-XXXXX');
        // Create terminal prefix
        const prefix = document.createElement('span');
        prefix.className = 'input-prefix';
        prefix.textContent = '>';
        inputEl.parentNode.insertBefore(prefix, inputEl);
      } else if (inputEl.id === 'clearance-code') {
        inputEl.setAttribute('placeholder', '********');
        // Create terminal prefix for password
        const prefix = document.createElement('span');
        prefix.className = 'input-prefix secure';
        prefix.textContent = '#';
        inputEl.parentNode.insertBefore(prefix, inputEl);
      }
    }
  });
}

// Add field research environment status indicators
function addEnvironmentStatus() {
  if (document.querySelector('.environment-status')) return;
  
  const statusPanel = document.createElement('div');
  statusPanel.className = 'environment-status';
  
  // Create status indicators
  const indicators = [
    { label: 'SAT UPLINK', status: 'ONLINE', className: 'status-ok' },
    { label: 'EM SENSORS', status: 'ACTIVE', className: 'status-ok' },
    { label: 'PERIMETER', status: randomStatus(), className: randomStatusClass() }
  ];
  
  indicators.forEach(indicator => {
    const item = document.createElement('div');
    item.className = 'status-item';
    item.innerHTML = `<span class="status-label">${indicator.label}:</span> <span class="status-value ${indicator.className}">${indicator.status}</span>`;
    statusPanel.appendChild(item);
  });
  
  // Add the status panel to the sign-in form
  const form = document.getElementById('sign-in-form');
  form.appendChild(statusPanel);
  
  // Occasionally update the perimeter status
  setInterval(() => {
    const perimeterStatus = document.querySelector('.status-item:nth-child(3) .status-value');
    if (perimeterStatus) {
      perimeterStatus.textContent = randomStatus();
      perimeterStatus.className = `status-value ${randomStatusClass()}`;
    }
  }, 15000);
}

// Generate random statuses for the environment panel
function randomStatus() {
  const statuses = ['SECURE', 'ACTIVE', 'CAUTION', 'ALERT', 'SCANNING'];
  return statuses[Math.floor(Math.random() * statuses.length)];
}

function randomStatusClass() {
  const statusClass = Math.random() > 0.7 ? 'status-warning' : 'status-ok';
  return statusClass;
}

// Add mission briefing note
function addMissionBriefing() {
  if (document.querySelector('.mission-note')) return;
  
  const missionTypes = [
    'RECON',
    'SPECIMEN COLLECTION',
    'WITNESS INTERVIEW',
    'ANOMALY VERIFICATION',
    'SITE MAPPING'
  ];
  
  const locations = [
    'PACIFIC NORTHWEST',
    'APPALACHIAN RIDGE',
    'LAKE CHAMPLAIN',
    'NEVADA TEST RANGE',
    'COASTAL WATERS'
  ];
  
  const mission = missionTypes[Math.floor(Math.random() * missionTypes.length)];
  const location = locations[Math.floor(Math.random() * locations.length)];
  
  const missionNote = document.createElement('div');
  missionNote.className = 'mission-note';
  missionNote.innerHTML = `<span class="mission-label">ACTIVE ASSIGNMENT:</span> ${mission} · ${location}`;
  
  const signInForm = document.getElementById('sign-in-form');
  if (signInForm) {
    signInForm.appendChild(missionNote);
  }
}

// Add biometric scanner for mobile
function addBiometricScanner() {
  const form = document.getElementById('sign-in-form');
  if (!form) return;
  
  const scannerContainer = document.createElement('div');
  scannerContainer.className = 'biometric-scanner mobile-only';
  
  // Add OR divider
  const divider = document.createElement('div');
  divider.className = 'auth-divider';
  divider.innerHTML = '<span>OR</span>';
  
  // Create scanner interface
  const scanner = document.createElement('div');
  scanner.className = 'fingerprint-scanner';
  scanner.innerHTML = `
    <div class="scanner-pad">
      <div class="scan-lines"></div>
      <div class="fingerprint-icon"></div>
      <div class="scan-result"></div>
    </div>
    <div class="scanner-label">PLACE FINGER ON SENSOR</div>
  `;
  
  // Add fake scanning interaction
  scanner.addEventListener('touchstart', startScanning);
  scanner.addEventListener('mousedown', startScanning);
  
  scannerContainer.appendChild(divider);
  scannerContainer.appendChild(scanner);
  
  // Insert after the authenticate button
  const authButton = form.querySelector('.btn-primary');
  authButton.parentNode.insertBefore(scannerContainer, authButton.nextSibling);
}

function startScanning(e) {
  e.preventDefault();
  const scanner = document.querySelector('.fingerprint-scanner');
  if (scanner.classList.contains('scanning')) return;
  
  scanner.classList.add('scanning');
  scanner.querySelector('.scanner-label').textContent = 'SCANNING...';
  
  // Get current security level
  const securityLevel = document.body.getAttribute('data-security-level') || 'normal';
  scanner.setAttribute('data-security-level', securityLevel);
  
  setTimeout(() => {
    scanner.classList.add('processing');
    scanner.querySelector('.scanner-label').textContent = 'PROCESSING...';
    
    setTimeout(() => {
      // Always fail the biometric scan
      scanner.classList.remove('scanning', 'processing');
      scanner.classList.add('scan-error');
      
      // Change error message based on security level
      let errorMessage = 'BIOMETRIC MISMATCH';
      if (securityLevel === 'warning') {
        errorMessage = 'SIGNATURE ANOMALY';
      } else if (securityLevel === 'danger') {
        errorMessage = 'CRITICAL AUTH FAILURE';
      }
      scanner.querySelector('.scanner-label').textContent = errorMessage;
      
      // Trigger failed login attempt
      window.loginAttemptsHandler.attempt();
      
      // Reset after delay
      setTimeout(() => {
        scanner.classList.remove('scan-error');
        scanner.removeAttribute('data-security-level');
        scanner.querySelector('.scanner-label').textContent = 'PLACE FINGER ON SENSOR';
      }, 2000);
    }, 1500);
  }, 2000);
}

// Add secret login credentials
const SECRET_CREDENTIALS = {
  id: 'CC-31415',
  code: 'CRYPTID-X'
};

function showAuthSuccess() {
  const successNotification = document.createElement('div');
  successNotification.className = 'auth-notification success';
  successNotification.textContent = 'WELCOME BACK, FIELD RESEARCHER';
  document.body.appendChild(successNotification);
  
  // Add success effects
  document.body.classList.add('auth-success');
  
  // Simulate system access
  setTimeout(() => {
    window.location.href = 'dashboard.html'; // Would redirect to dashboard if it existed
  }, 3000);
}

// Add thermal cursor hints
function addSecretHints() {
  const hints = [
    { text: 'ID FORMAT: CC-XXXXX', x: '15%', y: '20%' },
    { text: 'SPECIMEN CODE: CRYPTID', x: '85%', y: '40%' },
    { text: 'NUMERICAL KEY: 31415', x: '25%', y: '75%' },
    { text: 'DESIGNATION: X', x: '75%', y: '85%' }
  ];
  
  const hintContainer = document.createElement('div');
  hintContainer.className = 'thermal-hints';
  
  hints.forEach(hint => {
    const hintElement = document.createElement('div');
    hintElement.className = 'thermal-hint';
    hintElement.textContent = hint.text;
    hintElement.style.left = hint.x;
    hintElement.style.top = hint.y;
    hintContainer.appendChild(hintElement);
  });
  
  document.body.appendChild(hintContainer);
}

// Aptitude Tests System - Fixed
function startAptitudeTests() {
  console.log('Starting aptitude tests');
  
  // Create test container if it doesn't exist
  let aptitudeContainer = document.querySelector('.aptitude-test-container');
  
  if (!aptitudeContainer) {
    console.error('Aptitude container not found, creating fallback');
    
    // Create a simple fallback container
    aptitudeContainer = document.createElement('div');
    aptitudeContainer.className = 'aptitude-test-container';
    aptitudeContainer.innerHTML = `
      <div class="glass-panel aptitude-panel">
        <h2>Aptitude Test System</h2>
        <p>The test system cannot be loaded. Please try again.</p>
        <button type="button" class="btn-primary test-fallback-close">
          <span class="btn-text">RETURN</span>
        </button>
      </div>
    `;
    document.body.appendChild(aptitudeContainer);
    
    // Add close handler
    const closeBtn = aptitudeContainer.querySelector('.test-fallback-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        aptitudeContainer.classList.add('hidden');
        
        // Return to login form
        const registerContainer = document.getElementById('register-container');
        const signInContainer = document.getElementById('sign-in-container');
        
        if (registerContainer) registerContainer.classList.add('hidden');
        if (signInContainer) signInContainer.classList.remove('hidden');
      });
    }
  }
  
  // Make the container visible with a clear visual effect
  aptitudeContainer.style.display = 'flex';
  aptitudeContainer.classList.remove('hidden');
  
  // Force reflow to ensure transitions work
  void aptitudeContainer.offsetWidth;
  
  // Add active class with a slight delay
  setTimeout(() => {
    aptitudeContainer.classList.add('active');
    console.log('Aptitude container activated');
    
    // Initialize test content if available
    if (typeof initializeCurrentTest === 'function') {
      try {
        initializeCurrentTest(1);
        setupTestNavigation();
      } catch (err) {
        console.error('Error initializing tests:', err);
      }
    }
  }, 10);
}

// Initialize the current test based on test number
function initializeCurrentTest(testNumber) {
  // Hide all test sections and show current
  document.querySelectorAll('.test-section').forEach(section => {
    section.classList.remove('active');
  });
  
  const currentTest = document.querySelector(`.test-section[data-test="${testNumber}"]`);
  currentTest.classList.add('active');
  
  // Update progress indicator
  const progressFill = document.querySelector('.progress-fill');
  const progressLabel = document.querySelector('.progress-label');
  
  if (testNumber <= 3) {
    progressFill.style.width = `${(testNumber / 3) * 100}%`;
    progressLabel.textContent = `TEST ${testNumber}/3`;
  } else {
    progressFill.style.width = '100%';
    progressLabel.textContent = 'COMPLETE';
  }
  
  // Initialize specific test functionality
  switch(testNumber) {
    case 1:
      initPatternTest();
      break;
    case 2:
      initMemoryTest();
      break;
    case 3:
      initReactionTest();
      break;
    case 'results':
      initResultsScreen();
      break;
  }
  
  // Update continue button text
  const continueButton = document.querySelector('.test-continue');
  
  if (testNumber === 1) {
    continueButton.querySelector('.btn-text').textContent = 'BEGIN ASSESSMENT';
  } else if (testNumber === 'results') {
    continueButton.querySelector('.btn-text').textContent = 'FINALIZE REGISTRATION';
  } else {
    continueButton.querySelector('.btn-text').textContent = 'CONTINUE';
  }
}

// Set up next/continue button functionality
function setupTestNavigation() {
  console.log("Setting up test navigation");
  const continueButton = document.querySelector('.test-continue');
  
  if (!continueButton) {
    console.error("Continue button not found!");
    return;
  }
  
  let currentTestNumber = 1;
  let testResults = {
    pattern: false,
    memory: false,
    reaction: false
  };
  
  // Remove any existing event listeners (to prevent duplicates)
  const newButton = continueButton.cloneNode(true);
  if (continueButton.parentNode) {
    continueButton.parentNode.replaceChild(newButton, continueButton);
  }
  
  // Add event listener to the fresh button
  newButton.addEventListener('click', () => {
    console.log(`Test button clicked, current test: ${currentTestNumber}`);
    
    // Check if current test is passed
    if (currentTestNumber === 1) {
      testResults.pattern = checkPatternResult();
      console.log(`Pattern test result: ${testResults.pattern ? 'PASS' : 'FAIL'}`);
      
      if (!testResults.pattern) {
        showNotification('Please select the correct pattern', 'warning');
        flashButton(newButton);
        return;
      }
    } else if (currentTestNumber === 2) {
      testResults.memory = checkMemoryResult();
      console.log(`Memory test result: ${testResults.memory ? 'PASS' : 'FAIL'}`);
      
      if (!testResults.memory) {
        showNotification('Please identify all marker locations', 'warning');
        flashButton(newButton);
        return;
      }
    } else if (currentTestNumber === 3) {
      testResults.reaction = checkReactionResult();
      console.log(`Reaction test result: ${testResults.reaction ? 'PASS' : 'FAIL'}`);
      
      if (!testResults.reaction) {
        showNotification('Please capture all targets', 'warning');
        flashButton(newButton);
        return;
      }
    } else if (currentTestNumber === 'results') {
      completeRegistration();
      return;
    }
    
    // Move to next test
    if (currentTestNumber < 3) {
      currentTestNumber++;
    } else {
      currentTestNumber = 'results';
    }
    
    console.log(`Moving to test: ${currentTestNumber}`);
    initializeCurrentTest(currentTestNumber);
  });
}

// Flash button to indicate test not completed
function flashButton(button) {
  button.classList.add('shake');
  setTimeout(() => {
    button.classList.remove('shake');
  }, 500);
}

// Pattern Test Functions
function initPatternTest() {
  console.log("Initializing pattern test");
  const options = document.querySelectorAll('.pattern-option');
  
  // Clear previous selections
  options.forEach(option => {
    option.classList.remove('selected');
  });
  
  // Mark the first option (triangle) as correct using data attribute
  if (options.length > 0) {
    const triangleOption = Array.from(options).find(opt => 
      opt.textContent.includes('△') || 
      opt.textContent.trim() === '△'
    );
    
    if (triangleOption) {
      triangleOption.setAttribute('data-correct', 'true');
      triangleOption.classList.add('correct');
      console.log("Triangle option marked as correct");
    } else {
      // Fallback - mark the first option as correct if we can't find the triangle
      options[0].setAttribute('data-correct', 'true');
      options[0].classList.add('correct');
      console.log("First option marked as correct (fallback)");
    }
  }
  
  // Add click handlers to all options with improved reliability
  options.forEach((option, index) => {
    // Remove existing event listeners by cloning
    const newOption = option.cloneNode(true);
    if (option.parentNode) {
      option.parentNode.replaceChild(newOption, option);
    }
    
    // Add fresh event listener with enhanced logging
    newOption.addEventListener('click', () => {
      const optionText = newOption.textContent.trim();
      console.log(`Pattern option ${index} clicked: "${optionText}"`);
      console.log(`Option has data-correct: ${newOption.hasAttribute('data-correct')}`);
      
      // Deselect all options
      document.querySelectorAll('.pattern-option').forEach(o => {
        o.classList.remove('selected');
      });
      
      // Select clicked option
      newOption.classList.add('selected');
      
      // Add visual feedback
      const continueBtn = document.querySelector('.test-continue');
      if (continueBtn) {
        continueBtn.classList.add('pulse-once');
        setTimeout(() => continueBtn.classList.remove('pulse-once'), 500);
      }
    });
  });
}

function checkPatternResult() {
  const selectedOption = document.querySelector('.pattern-option.selected');
  
  if (!selectedOption) {
    console.log("No option selected");
    return false;
  }
  
  // Debug the selected option
  console.log(`Selected option: ${selectedOption.textContent.trim()}`);
  console.log(`Has correct data attribute: ${selectedOption.getAttribute('data-correct') === 'true'}`);
  console.log(`Has correct class: ${selectedOption.classList.contains('correct')}`);
  
  // Check multiple ways to determine if the option is correct
  const isCorrectByAttribute = selectedOption.getAttribute('data-correct') === 'true';
  const isCorrectByClass = selectedOption.classList.contains('correct');
  
  // For testing purposes, always force triangle (first option) to be correct
  const isFirstOption = Array.from(document.querySelectorAll('.pattern-option')).indexOf(selectedOption) === 0;
  
  // Log which method worked
  if (isCorrectByAttribute) console.log("Correct by data attribute");
  if (isCorrectByClass) console.log("Correct by class");
  if (isFirstOption) console.log("Correct by being first option (fallback)");
  
  // Return true if any method indicates it's correct
  return isCorrectByAttribute || isCorrectByClass || isFirstOption;
}

// Memory Test Functions
function initMemoryTest() {
  console.log("Initializing memory test");
  
  // Reset any previous state
  const recallSection = document.querySelector('.memory-recall');
  const mapSection = document.querySelector('.memory-map');
  const mapOverlay = document.querySelector('.map-overlay');
  
  // Ensure sections are properly set up
  if (recallSection) recallSection.classList.add('hidden');
  if (mapOverlay) {
    mapOverlay.classList.remove('visible');
    mapOverlay.textContent = '';
  }
  
  // Make sure the memory map is visible with the markers
  if (mapSection) {
    mapSection.classList.remove('hidden');
    mapSection.style.opacity = '1';
  }
  
  // Clear selections
  document.querySelectorAll('.recall-cell').forEach(cell => {
    cell.classList.remove('selected');
  });
  
  // Ensure the memory locations are visible at first
  document.querySelectorAll('.memory-marker').forEach(marker => {
    marker.style.opacity = '1';
  });
  
  // Start countdown
  let countdown = 5;
  const countdownSpan = document.querySelector('.countdown');
  if (countdownSpan) countdownSpan.textContent = countdown;
  
  console.log("Memory test countdown started");
  const countdownInterval = setInterval(() => {
    countdown--;
    if (countdownSpan) countdownSpan.textContent = countdown;
    
    if (countdown <= 0) {
      clearInterval(countdownInterval);
      
      // Hide the markers by adding an overlay
      if (mapOverlay) {
        mapOverlay.classList.add('visible');
        mapOverlay.textContent = 'RECALL';
      }
      
      // Or hide the markers directly
      document.querySelectorAll('.memory-marker').forEach(marker => {
        marker.style.opacity = '0';
      });
      
      if (mapSection) {
        mapSection.style.opacity = '0.5'; // Dim the map to indicate recall mode
      }
      
      console.log("Memory markers hidden, showing recall grid");
      // Show recall grid after a moment
      setTimeout(() => {
        if (recallSection) recallSection.classList.remove('hidden');
        
        // Setup click handlers for recall cells
        document.querySelectorAll('.recall-cell').forEach(cell => {
          // Remove previous event listeners by cloning
          const newCell = cell.cloneNode(true);
          if (cell.parentNode) cell.parentNode.replaceChild(newCell, cell);
          
          newCell.addEventListener('click', () => {
            newCell.classList.toggle('selected');
            console.log(`Cell ${newCell.dataset.location} toggled`);
          });
        });
      }, 1000);
    }
  }, 1000);
}

function checkMemoryResult() {
  // Get selected cells
  const selectedCells = Array.from(document.querySelectorAll('.recall-cell.selected'));
  const selectedLocations = selectedCells.map(cell => cell.dataset.location);
  
  // Correct locations from the map
  const correctLocations = ['A3', 'C1', 'D4'];
  
  // Check if all correct and no incorrect
  const allCorrect = correctLocations.every(loc => selectedLocations.includes(loc));
  const noIncorrect = selectedLocations.every(loc => correctLocations.includes(loc));
  
  return allCorrect && noIncorrect;
}

// Reaction Test Functions
function initReactionTest() {
  const arena = document.querySelector('.reaction-arena');
  const counter = arena.querySelector('.capture-counter span');
  let captures = 0;
  
  // Reset counter
  counter.textContent = '0';
  
  // Clear any existing targets
  document.querySelectorAll('.cryptid-target').forEach(target => target.remove());
  
  // Function to create a target
  function createTarget() {
    if (captures >= 4) return; // Stop creating targets if threshold reached
    
    const target = document.createElement('div');
    target.className = 'cryptid-target';
    
    // Add the logo SVG image
    const logoImg = document.createElement('img');
    logoImg.src = 'logo.svg';
    logoImg.alt = 'Cryptid Collective Logo';
    logoImg.className = 'target-logo';
    target.appendChild(logoImg);
    
    // Random position
    const x = Math.floor(Math.random() * (arena.offsetWidth - 60)) + 10;
    const y = Math.floor(Math.random() * (arena.offsetHeight - 50)) + 10;
    target.style.left = `${x}px`;
    target.style.top = `${y}px`;
    
    // Random size (40-70px)
    const size = Math.floor(Math.random() * 30) + 40;
    logoImg.style.width = `${size}px`;
    logoImg.style.height = `${size}px`;
    
    // Add to arena
    arena.appendChild(target);
    
    // Make visible after a moment (allows transition)
    setTimeout(() => {
      target.classList.add('visible');
      
      // Capture click
      target.addEventListener('click', () => {
        target.classList.add('captured');
        captures++;
        counter.textContent = captures;
        
        // Play capture sound
        playCapturePing();
        
        // Remove after animation
        setTimeout(() => {
          target.remove();
        }, 500);
      });
      
      // Disappear after random time
      const disappearTime = Math.random() * 2000 + 1000;
      setTimeout(() => {
        if (target.parentNode) { // Check if still in DOM
          target.remove();
        }
      }, disappearTime);
      
    }, 100);
    
    // Create next target after random delay
    const nextTargetDelay = Math.random() * 1500 + 500;
    setTimeout(createTarget, nextTargetDelay);
  }
  
  // Start spawning targets
  setTimeout(createTarget, 500);
}

// Add a simple capture sound effect
function playCapturePing() {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.type = 'sine';
    oscillator.frequency.value = 880; // Higher frequency for success sound
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    gainNode.gain.value = 0.1;
    oscillator.start();
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.2);
    
    setTimeout(() => {
      oscillator.stop();
    }, 200);
  } catch (e) {
    console.log('Audio context error:', e);
  }
}

// Check reaction test result
function checkReactionResult() {
  // Get the current number of captures from the counter
  const captureCounter = document.querySelector('.capture-counter span');
  
  if (!captureCounter) {
    console.error("Capture counter not found");
    return false;
  }
  
  const captures = parseInt(captureCounter.textContent || '0');
  console.log(`Reaction test captures: ${captures}/4`);
  
  // Test passes if user captured at least 4 targets
  return captures >= 4;
}

// Update the target logo styles to use black glow
const targetLogoStyles = document.createElement('style');
targetLogoStyles.textContent = `
  .cryptid-target {
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transform: scale(0.5);
    transition: opacity 0.3s, transform 0.3s;
    cursor: pointer;
    filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.8));
  }
  
  .cryptid-target.visible {
    opacity: 1;
    transform: scale(1);
  }
  
  .cryptid-target.captured {
    transform: scale(1.5);
    opacity: 0;
    transition: transform 0.5s, opacity 0.5s;
  }
  
  .target-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    filter: brightness(1.5) drop-shadow(0 0 3px #000);
  }
`;
document.head.appendChild(targetLogoStyles);

// Results Screen Functions
function initResultsScreen() {
  // Show success animations
  playSuccessEffects();
  
  // After delay, enable final button
  setTimeout(() => {
    document.querySelector('.test-continue').disabled = false;
  }, 2000);
}

function playSuccessEffects() {
  // Add scanner line animation
  const resultsPanel = document.querySelector('.results-panel');
  const scanner = document.createElement('div');
  scanner.className = 'scanner-line';
  resultsPanel.appendChild(scanner);
  
  // Play success sound
  playSuccessSound();
}

function playSuccessSound() {
  const audioContext = new (window.AudioContext || window.webkitAudioContext)();
  
  // Create success sequence
  const notes = [440, 554, 659, 880];
  
  notes.forEach((freq, index) => {
    setTimeout(() => {
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();
      
      oscillator.type = 'sine';
      oscillator.frequency.value = freq;
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);
      
      gainNode.gain.value = 0.1;
      oscillator.start();
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.5);
      
      setTimeout(() => {
        oscillator.stop();
      }, 500);
    }, index * 200);
  });
}

function completeRegistration() {
  // Show success message
  const successNotification = document.createElement('div');
  successNotification.className = 'auth-notification success';
  successNotification.textContent = 'FIELD RESEARCHER PROFILE INITIALIZED';
  document.body.appendChild(successNotification);
  
  console.log("Registration complete, returning to login");
  
  // Close aptitude test container
  const aptitudeContainer = document.querySelector('.aptitude-test-container');
  if (aptitudeContainer) {
    aptitudeContainer.classList.remove('active');
    
    // After delay, redirect to sign-in
    setTimeout(() => {
      aptitudeContainer.classList.add('hidden');
      aptitudeContainer.style.display = 'none'; // Ensure it's fully hidden
      
      // Return to login screen - make sure both steps happen
      const registerContainer = document.getElementById('register-container');
      const signInContainer = document.getElementById('sign-in-container');
      
      if (registerContainer) registerContainer.classList.add('hidden');
      if (signInContainer) signInContainer.classList.remove('hidden');
      
      // Force reflow
      void document.body.offsetWidth;
      
      // Show an additional success message
      showNotification('Registration complete. Welcome to Cryptid Collective!', 'success');
      
      // Remove the original success notification
      successNotification.remove();
    }, 3000);
  }
}

// Play error sound
function playErrorSound(frequency = 200, volume = 0.2) {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.type = 'sawtooth';
    oscillator.frequency.value = frequency;
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    gainNode.gain.value = volume;
    oscillator.start();
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.2);
    
    setTimeout(() => {
      oscillator.stop();
    }, 200);
  } catch (e) {
    console.log('Audio context error:', e);
  }
}

// Initialize effects
document.addEventListener('DOMContentLoaded', () => {
  createParticleField();
  addScannerLine();
  addTopographicOverlay(); // Add topographic map background
  addClassifiedStamp(); // Add classified stamp effect
  
  // Add flavor header
  addFlavorHeader();
  
  // Add glitch effect to headings
  document.querySelectorAll('h1').forEach(h1 => {
    h1.addEventListener('mouseover', () => glitchText(h1));
  });
  
  // Run typing effect on form labels only once at load time
  document.querySelectorAll('label').forEach(label => {
    const originalText = label.textContent;
    label.textContent = ''; // Clear the text first
    
    // Add a small delay for a staggered effect
    setTimeout(() => {
      typeText(label, originalText);
    }, 100 * Math.random() * 10); // Random delay between 0-1000ms
    
    // Remove the mouseenter event that would cause repeated typing
    label.removeEventListener('mouseenter', () => {
      typeText(label, originalText);
    });
  });
  
  // Create improved help hint with better formatting
  createHelpHint();
  
  // Add a subtle "field notes" timestamp label
  if (timestampElement) {
    const timeLabel = document.createElement('span');
    timeLabel.className = 'time-label';
    timeLabel.textContent = 'FIELD LOG: ';
    timestampElement.parentNode.insertBefore(timeLabel, timestampElement);
  }
  
  addShineEffect(); // Add the new shine effect
  
  // Add new immersive elements
  enhanceFormExperience();
  addEnvironmentStatus();
  addMissionBriefing();
  addBiometricScanner(); // Add biometric scanner for mobile
  
  // Add subtle animation to form elements
  animateFormElements();
  
  // Add secret hints
  addSecretHints();
});

// Create improved help hint with better formatting
function createHelpHint() {
  // Remove existing help hint if present
  document.querySelectorAll('.help-hint').forEach(hint => hint.remove());
  
  // Create new help hint with better structure
  const helpHint = document.createElement('div');
  helpHint.className = 'help-hint';
  
  // Improved HTML structure with better spacing
  helpHint.innerHTML = `
    <div class="help-hint-content">
      <div class="hint-item">
        <span class="hint-label">FIELD EQUIPMENT:</span> 
        <span class="hint-shortcut"><strong>M</strong></span> 
        <span class="hint-desc">for voice calibration</span>
      </div>
      <div class="hint-divider">|</div>
      <div class="hint-item">
        <span class="hint-shortcut"><strong>RESET</strong></span> 
        <span class="hint-desc">for system restart</span>
      </div>
    </div>
  `;
  
  document.body.appendChild(helpHint);
  
  // Add CSS for the improved help hint
  const helpHintStyles = document.createElement('style');
  helpHintStyles.textContent = `
    .help-hint {
      position: fixed;
      bottom: 10px;
      left: 0;
      width: 100%;
      display: flex;
      justify-content: center;
      z-index: 100;
      pointer-events: none;
      font-family: var(--font-mono);
    }
    
    .help-hint-content {
      background: rgba(0, 0, 0, 0.6);
      border: 1px solid rgba(57, 240, 217, 0.2);
      border-radius: 4px;
      padding: 4px 12px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.7rem;
      color: var(--misty-gray);
      letter-spacing: 0.5px;
    }
    
    .hint-item {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .hint-label {
      color: var(--bioluminescent-teal);
      opacity: 0.8;
      margin-right: 4px;
    }
    
    .hint-shortcut {
      color: var(--bioluminescent-teal);
    }
    
    .hint-divider {
      color: rgba(96, 125, 139, 0.5);
      margin: 0 4px;
    }
    
    @media (max-width: 768px) {
      .help-hint-content {
        font-size: 0.6rem;
        padding: 3px 8px;
      }
      
      .hint-item {
        gap: 2px;
      }
      
      .hint-divider {
        margin: 0 2px;
      }
    }
  `;
  document.head.appendChild(helpHintStyles);
}

// Add subtle scanning animation to form
function animateFormElements() {
  const formGroups = document.querySelectorAll('.form-group');
  formGroups.forEach((group, index) => {
    setTimeout(() => {
      group.classList.add('scanned');
    }, index * 300);
  });
}

// Add the needed CSS for the loading overlay
const style = document.createElement('style');
style.textContent = `
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(30, 59, 44, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }
  
  .scanning-effect {
    width: 300px;
    height: 5px;
    background: linear-gradient(to right, transparent, var(--bioluminescent-teal), transparent);
    position: relative;
    margin-bottom: 20px;
    overflow: hidden;
  }
  
  .scanning-effect::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, transparent, white, transparent);
    animation: scan 1.5s infinite;
  }
  
  .loading-text {
    font-family: var(--font-mono);
    color: var(--bioluminescent-teal);
    letter-spacing: 1px;
  }
  
  @keyframes scan {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }
  
  .error-flash {
    animation: errorPulse 0.6s;
  }
  
  @keyframes errorPulse {
    0%, 100% { box-shadow: none; }
    50% { box-shadow: 0 0 8px #f44336; }
  }
`;
document.head.appendChild(style);

// Security system - track login attempts and activate lockout
document.addEventListener('DOMContentLoaded', function() {
  // Track login attempts
  let loginAttempts = 0;
  const MAX_ATTEMPTS = 3;
  
  // Define complete color schemes for different security states - completely remove green from warning/danger
  const colorSchemes = {
    normal: {
      // Primary colors
      '--expedition-green': '#1E3B2C',
      '--midnight-charcoal': '#2D3033',
      '--misty-gray': '#607D8B',
      
      // Accent colors
      '--bioluminescent-teal': '#39F0D9',
      '--evidence-amber': '#FF9800',
      '--scanner-green': '#76FF03',

      // Background colors
      '--background-primary': 'linear-gradient(135deg, #2D3033, #1E3B2C)',
      '--container-bg': 'rgba(45, 48, 51, 0.1)',
      '--container-border': 'rgba(255, 255, 255, 0.05)',
      '--container-shadow': '0 4px 30px rgba(0, 0, 0, 0.2), 0 0 50px rgba(57, 240, 217, 0.1), inset 0 0 15px rgba(57, 240, 217, 0.05)',
      '--particle-color': 'rgba(57, 240, 217, 0.6)',
      '--glitch-line-color': 'rgba(57, 240, 217, 0.5)'
    },
    warning: {
      // Primary colors - pure amber/gold with NO green
      '--expedition-green': '#664911', // Dark amber/gold instead of green
      '--midnight-charcoal': '#4D3B22', // Warm dark instead of charcoal
      '--misty-gray': '#B39356', // Gold-tinted gray
      
      // Accent colors - all amber/yellow family
      '--bioluminescent-teal': '#FFC107', // Yellow
      '--evidence-amber': '#FF9800', // Orange
      '--scanner-green': '#FFD740', // Gold

      // Background colors - warm amber
      '--background-primary': 'linear-gradient(135deg, #4D3B22, #664911)',
      '--container-bg': 'rgba(77, 59, 34, 0.1)',
      '--container-border': 'rgba(255, 193, 7, 0.1)',
      '--container-shadow': '0 4px 30px rgba(0, 0, 0, 0.2), 0 0 50px rgba(255, 193, 7, 0.2), inset 0 0 15px rgba(255, 193, 7, 0.1)',
      '--particle-color': 'rgba(255, 193, 7, 0.6)',
      '--glitch-line-color': 'rgba(255, 193, 7, 0.5)'
    },
    danger: {
      // Primary colors - complete red with NO green
      '--expedition-green': '#661111', // Dark red instead of green
      '--midnight-charcoal': '#4D2222', // Red dark instead of charcoal
      '--misty-gray': '#B35656', // Red-tinted gray
      
      // Accent colors - all red family
      '--bioluminescent-teal': '#F44336', // Red
      '--evidence-amber': '#FF5722', // Deep orange
      '--scanner-green': '#FF1744', // Red accent

      // Background colors - deep red
      '--background-primary': 'linear-gradient(135deg, #4D2222, #661111)',
      '--container-bg': 'rgba(77, 34, 34, 0.2)',
      '--container-border': 'rgba(244, 67, 54, 0.1)',
      '--container-shadow': '0 4px 30px rgba(0, 0, 0, 0.3), 0 0 50px rgba(244, 67, 54, 0.2), inset 0 0 15px rgba(244, 67, 54, 0.1)',
      '--particle-color': 'rgba(244, 67, 54, 0.6)',
      '--glitch-line-color': 'rgba(244, 67, 54, 0.5)'
    }
  };
  
  // Get form element
  const loginForm = document.getElementById('login-form') || document.querySelector('form');
  
  // Find or create security indicators if they don't exist
  if (!document.querySelector('.security-indicators')) {
    // Create security indicators inside the authenticate button
    const authButton = document.querySelector('.btn-primary');
    
    const indicators = document.createElement('div');
    indicators.className = 'security-indicators';
    
    for (let i = 0; i < MAX_ATTEMPTS; i++) {
      const dot = document.createElement('div');
      dot.className = 'indicator';
      indicators.appendChild(dot);
    }
    
    authButton.appendChild(indicators);
  }
  
  // Create notification element for auth failures
  const notification = document.createElement('div');
  notification.className = 'auth-notification';
  notification.style.display = 'none';
  document.body.appendChild(notification); // Append to body instead of glass panel for better visibility
  
  // Expose the attempt function for the form submit handler
  window.loginAttemptsHandler = {
    attempt: function() {
      loginAttempts++;
      updateSecurityIndicators();
      
      // Update color scheme based on attempts
      updateColorScheme(loginAttempts);
      
      // Show aggressive failure notification
      showAuthFailure(loginAttempts);
      
      if (loginAttempts >= MAX_ATTEMPTS) {
        activateLockout();
      }
    }
  };
  
  // Original form submit handler remains for compatibility with existing code
  if (loginForm) {
    // Keep existing handler but make it a no-op to avoid double-counting attempts
    loginForm.removeEventListener('submit', originalSubmitHandler);
  }
  
  function originalSubmitHandler(event) {
    event.preventDefault();
  }
  
  // Update indicators based on failed attempts
  function updateSecurityIndicators() {
    const indicators = document.querySelectorAll('.indicator');
    
    indicators.forEach((indicator, index) => {
      if (index < loginAttempts) {
        indicator.classList.remove('active');
        
        if (loginAttempts === MAX_ATTEMPTS - 1 && index === loginAttempts - 1) {
          // Last attempt - warning state
          indicator.classList.add('warning');
        } else if (loginAttempts === MAX_ATTEMPTS) {
          // Lockout - danger state
          indicator.classList.add('danger');
        } else {
          // Normal failed attempt
          indicator.classList.add('active');
        }
      }
    });
  }
  
  // Update color scheme based on number of failed attempts
  function updateColorScheme(attempts) {
    let scheme;
    
    if (attempts === 1) {
      scheme = colorSchemes.normal; 
      document.body.setAttribute('data-security-level', 'normal');
    } else if (attempts === 2) {
      scheme = colorSchemes.warning; 
      document.body.setAttribute('data-security-level', 'warning');
    } else if (attempts >= 3) {
      scheme = colorSchemes.danger;
      document.body.setAttribute('data-security-level', 'danger');
    }
    
    // Apply the complete color scheme to CSS variables
    for (const [variable, value] of Object.entries(scheme)) {
      document.documentElement.style.setProperty(variable, value);
    }
    
    // Apply additional style changes based on security state
    const glassPanel = document.querySelector('.glass-panel');
    
    if (attempts === 2) {
      // Update background gradient
      document.querySelector('.background').style.background = scheme['--background-primary'];
      
      // Update glassmorphism panel
      if (glassPanel) {
        glassPanel.style.background = scheme['--container-bg'];
        glassPanel.style.borderColor = scheme['--container-border'];
        glassPanel.style.boxShadow = scheme['--container-shadow'];
        glassPanel.classList.add('warning-pulse');
      }
      
      // Update ALL color elements to match warning scheme
      document.querySelectorAll('.particle').forEach(particle => {
        particle.style.background = scheme['--particle-color'];
      });
      
      // Update scanner line color
      document.querySelector('.scanner-line').style.background = 
        `linear-gradient(90deg, transparent, ${scheme['--bioluminescent-teal']}, transparent)`;
        
      // Update any glitch lines that might appear
      document.querySelectorAll('.glitch-line').forEach(line => {
        line.style.background = scheme['--glitch-line-color'];
      });
      
      // Update radar pings
      document.documentElement.style.setProperty('--ping-color', scheme['--bioluminescent-teal']);
      
      // Update flavor header
      glitchCoordinates('warning');
      document.querySelector('.header-batch').classList.add('warning-alert');
    } 
    else if (attempts >= 3) {
      // Update background gradient more intensely
      document.querySelector('.background').style.background = scheme['--background-primary'];
      
      // Update glassmorphism panel to danger state
      if (glassPanel) {
        glassPanel.classList.remove('warning-pulse');
        glassPanel.style.background = scheme['--container-bg'];
        glassPanel.style.borderColor = scheme['--container-border'];
        glassPanel.style.boxShadow = scheme['--container-shadow'];
        glassPanel.classList.add('danger-pulse');
      }
      
      // Update ALL color elements to match danger scheme
      document.querySelectorAll('.particle').forEach(particle => {
        particle.style.background = scheme['--particle-color'];
      });
      
      // Update scanner line color
      document.querySelector('.scanner-line').style.background = 
        `linear-gradient(90deg, transparent, ${scheme['--bioluminescent-teal']}, transparent)`;
      
      // Update any glitch lines that might appear
      document.querySelectorAll('.glitch-line').forEach(line => {
        line.style.background = scheme['--glitch-line-color'];
      });
      
      // Update radar pings
      document.documentElement.style.setProperty('--ping-color', scheme['--bioluminescent-teal']);
      
      // Update flavor header
      glitchCoordinates('danger');
      document.querySelector('.header-batch').classList.add('danger-alert');
      document.querySelector('.header-batch').classList.remove('warning-alert');
      document.querySelector('.header-phrase').classList.add('danger-text');
    }
    
    // Apply color updates to biometric scanner
    const scanner = document.querySelector('.fingerprint-scanner');
    if (scanner) {
      const scannerPad = scanner.querySelector('.scanner-pad');
      const scanLines = scanner.querySelector('.scan-lines');
      
      if (attempts === 1) {
        scannerPad.style.borderColor = scheme['--bioluminescent-teal'];
        scannerPad.style.boxShadow = `0 0 15px ${scheme['--bioluminescent-teal']}`;
        scanLines.style.background = `repeating-linear-gradient(transparent, transparent 2px, ${scheme['--bioluminescent-teal']} 2px, ${scheme['--bioluminescent-teal']} 4px)`;
      } else if (attempts === 2) {
        scannerPad.style.borderColor = scheme['--evidence-amber'];
        scannerPad.style.boxShadow = `0 0 15px ${scheme['--evidence-amber']}`;
        scanLines.style.background = `repeating-linear-gradient(transparent, transparent 2px, ${scheme['--evidence-amber']} 2px, ${scheme['--evidence-amber']} 4px)`;
      } else if (attempts >= 3) {
        scannerPad.style.borderColor = scheme['--bioluminescent-teal'];
        scannerPad.style.boxShadow = `0 0 20px ${scheme['--bioluminescent-teal']}`;
        scanLines.style.background = `repeating-linear-gradient(transparent, transparent 2px, ${scheme['--bioluminescent-teal']} 2px, ${scheme['--bioluminescent-teal']} 4px)`;
      }
    }
  }
  
  // Show authentication failure notification
  function showAuthFailure(attempt) {
    let message, severity;
    
    if (attempt === 1) {
      message = 'FIELD EQUIPMENT CALIBRATION ERROR... TRY AGAIN!';
      severity = 'notice'; // Changed from 'error' to 'notice' for green state
    } else if (attempt === 2) {
      message = 'EXPEDITION ALERT: ACCESS ANOMALY DETECTED';
      severity = 'warning';
      
      // Add coordinate scrambling effect for warning state
      document.querySelector('.coordinates').innerHTML = generateRandomCoordinates();
      document.querySelector('.coordinates').classList.add('warning-flicker');
    } else {
      message = 'CRITICAL: UNAUTHORIZED ACCESS PROTOCOL';
      severity = 'critical';
      
      // Show random cryptid silhouette at danger state
      showRandomCryptid();
    }
    
    notification.textContent = message;
    notification.className = `auth-notification ${severity}`;
    notification.style.display = 'block';
    
    // For critical notifications, position in the center of the screen
    if (attempt === 3) {
      notification.style.position = 'fixed';
      notification.style.top = '50%';
      notification.style.left = '50%';
      notification.style.transform = 'translate(-50%, -50%)';
      notification.style.zIndex = '10000'; // Ensure it's above everything
      notification.style.padding = '20px 30px';
      notification.style.fontSize = '1.2rem';
      notification.style.boxShadow = '0 0 30px rgba(244, 67, 54, 0.4)';
    } else {
      // Reset position for non-critical notifications
      notification.style.position = 'absolute';
      notification.style.top = '10px';
      notification.style.left = '50%';
      notification.style.transform = 'translateX(-50%)';
      notification.style.zIndex = '100';
      notification.style.padding = '';
      notification.style.fontSize = '';
      notification.style.boxShadow = '';
    }
    
    // Add typing terminal effect to error notification
    simulateTyping(notification, notification.textContent);
    
    // Shake the form panel
    const glassPanel = document.querySelector('.glass-panel');
    glassPanel.classList.add('shake');
    setTimeout(() => glassPanel.classList.remove('shake'), 500);
    
    // Play error sound with increasing intensity
    const frequency = 200 + (attempt * 100);
    playErrorSound(frequency, 0.2 + (attempt * 0.1));
    
    // Hide notification after delay (longer for critical)
    setTimeout(() => {
      notification.style.display = 'none';
      
      // Only first failure gets audio feedback to avoid overwhelming
      if (attempt === 1) {
        speakNotification("Field equipment calibration error. Please recalibrate access credentials.");
      } else if (attempt === 2) {
        speakNotification("Alert. Unauthorized access attempt detected. Security protocols engaged.");
      }
    }, attempt === 3 ? 5000 : 3000); // Longer display time for critical notification
  }
  
  // Generate random coordinates for warning state
  function generateRandomCoordinates() {
    const lat = (Math.random() * 180 - 90).toFixed(6);
    const lng = (Math.random() * 360 - 180).toFixed(6);
    return `LAT: ${lat} LNG: ${lng}`;
  }
  
  // Show a random cryptid silhouette
  function showRandomCryptid() {
    const cryptids = [
      'M10,30 C20,10 40,10 50,30 C40,15 20,15 10,30 Z', // Sea monster
      'M10,10 L20,30 L30,10 Q20,0 10,10 Z', // Mothman
      'M5,20 Q15,5 25,20 Q35,5 45,20 L45,30 Q25,40 5,30 Z' // UFO
    ];
    
    const cryptid = document.createElement('div');
    cryptid.className = 'danger-cryptid';
    const randomCryptid = cryptids[Math.floor(Math.random() * cryptids.length)];
    
    cryptid.innerHTML = `<svg viewBox="0 0 50 40" xmlns="http://www.w3.org/2000/svg">
      <path d="${randomCryptid}" fill="rgba(244, 67, 54, 0.3)" />
    </svg>`;
    
    document.body.appendChild(cryptid);
    
    setTimeout(() => {
      cryptid.classList.add('fade-out');
      setTimeout(() => cryptid.remove(), 1000);
    }, 3000);
  }
  
  // Text corruption effect for danger state
  function corruptText(element) {
    const originalText = element.textContent;
    const corruptChars = '!@#$%^&*<>/\\|{}[]01';
    
    let timesRun = 0;
    const corruptionInterval = setInterval(() => {
      timesRun++;
      if (timesRun > 20) {
        clearInterval(corruptionInterval);
        setTimeout(() => {
          element.textContent = originalText; // Reset eventually
        }, 5000);
        return;
      }
      
      // Corrupt a random selection of characters
      let corruptedText = '';
      for (let i = 0; i < originalText.length; i++) {
        if (Math.random() > 0.7) {
          corruptedText += corruptChars.charAt(Math.floor(Math.random() * corruptChars.length));
        } else {
          corruptedText += originalText.charAt(i);
        }
      }
      
      element.textContent = corruptedText;
    }, 200);
  }
  
  // Simulate typing effect
  function simulateTyping(element, finalText, speed = 30) {
    const originalText = finalText;
    element.textContent = '';
    
    let i = 0;
    const typingInterval = setInterval(() => {
      if (i < originalText.length) {
        element.textContent += originalText.charAt(i);
        i++;
      } else {
        clearInterval(typingInterval);
      }
    }, speed);
  }
  
  // Scramble timestamp during danger state
  function startGlitchingTimestamp() {
    const timestamp = document.getElementById('timestamp');
    if (!timestamp) return;
    
    const originalUpdateTimestamp = updateTimestamp;
    updateTimestamp = function() {
      const now = new Date();
      // Generate random parts of timestamp
      const year = Math.floor(Math.random() * 50) + 2000;
      const month = Math.floor(Math.random() * 12) + 1;
      const day = Math.floor(Math.random() * 28) + 1;
      const hour = Math.floor(Math.random() * 24);
      const minute = Math.floor(Math.random() * 60);
      const second = Math.floor(Math.random() * 60);
      
      const glitchedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')} ${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:${second.toString().padStart(2, '0')} UTC`;
      
      timestamp.textContent = glitchedDate;
    };
  }
  
  // Text-to-speech notifications (with optional mute)
  function speakNotification(text) {
    const brandedMessages = {
      "Authentication unsuccessful. Please try again.": "Field equipment calibration error. Please recalibrate access credentials.",
      "Warning. Security breach imminent.": "Alert. Unauthorized access attempt detected. Security protocols engaged.",
      "Critical security breach detected. System lockdown initiated.": "Critical alert. Security containment protocols activated. System locked for specimen protection."
    };
    
    // Use branded version if available
    const brandedText = brandedMessages[text] || text;
    
    if (window.speechSynthesis && !window.muteVoiceFeedback) {
      const speech = new SpeechSynthesisUtterance(brandedText);
      speech.volume = 0.7;
      speech.rate = 0.9;
      speech.pitch = 0.9;
      window.speechSynthesis.speak(speech);
    }
  }

  // Voice feedback toggle
  window.toggleVoiceFeedback = function() {
    window.muteVoiceFeedback = !window.muteVoiceFeedback;
    const status = window.muteVoiceFeedback ? "muted" : "enabled";
    
    // Show feedback
    const feedbackEl = document.createElement('div');
    feedbackEl.className = 'temp-feedback';
    feedbackEl.textContent = `Voice alerts ${status}`;
    document.body.appendChild(feedbackEl);
    
    setTimeout(() => {
      feedbackEl.remove();
    }, 2000);
  };
  
  // Add "M" keybinding to toggle voice
  document.addEventListener('keydown', function(e) {
    // Press M key to toggle mute
    if (e.keyCode === 77) {
      window.toggleVoiceFeedback();
    }
  });
});

// Activate dramatic lockout state
function activateLockout() {
  const glassPanel = document.querySelector('.glass-panel');
  glassPanel.classList.add('lockout');
  
  // Create and inject a powerful SVG filter that will apply to everything
  const svgFilter = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svgFilter.style.width = '0';
  svgFilter.style.height = '0';
  svgFilter.style.position = 'absolute';
  svgFilter.setAttribute('aria-hidden', 'true');
  svgFilter.innerHTML = `
    <defs>
      <filter id="security-breach-filter">
        <!-- Turn everything red -->
        <feColorMatrix type="matrix" values="
          1.0 0.0 0.0 0 0.2
          0.2 0.3 0.0 0 0
          0.2 0.0 0.3 0 0
          0   0   0   1 0" />
      </filter>
    </defs>
  `;
  document.body.appendChild(svgFilter);
  
  // Apply filter to the HTML element (highest level)
  document.documentElement.classList.add('security-breach-active');
  
  // Force all elements to use red colors by adding a special class to <html>
  document.documentElement.classList.add('total-breach');
  
  // Create a full-screen overlay with warning pattern
  const breachOverlay = document.createElement('div');
  breachOverlay.className = 'breach-overlay';
  document.body.appendChild(breachOverlay);
  
  // Continue with the rest of the lockout sequence
  document.body.classList.add('security-breach');
  
  // DIRECT STYLE OVERRIDES - Force specific elements to be red that might resist the CSS rules
  
  // Force the authenticate button to red
  const authButton = document.querySelector('.btn-primary');
  if (authButton) {
    authButton.style.backgroundColor = 'rgba(153, 0, 0, 0.3)';
    authButton.style.borderColor = 'rgba(255, 0, 0, 0.4)';
    authButton.style.color = '#ff6666';
    authButton.style.boxShadow = '0 0 15px rgba(255, 0, 0, 0.4)';
    
    // Also force any child elements to be red
    authButton.querySelectorAll('*').forEach(el => {
      el.style.color = '#ff6666';
      el.style.borderColor = 'rgba(255, 0, 0, 0.4)';
    });
  }
  
  // Force all input fields to red
  document.querySelectorAll('input').forEach(input => {
    input.style.borderColor = '#990000';
    input.style.color = '#ff6666';
    input.style.boxShadow = 'none';
    
    // Force the glow element next to inputs to be red
    const glow = input.parentElement.querySelector('.input-glow');
    if (glow) {
      glow.style.boxShadow = '0 0 8px rgba(255, 0, 0, 0.4)';
      glow.style.backgroundColor = 'rgba(153, 0, 0, 0.1)';
    }
  });
  
  // Force the classified stamp to be red
  const stamp = document.querySelector('.classified-stamp');
  if (stamp) {
    stamp.style.color = 'rgba(255, 0, 0, 0.7)';
    stamp.style.borderColor = 'rgba(255, 0, 0, 0.7)';
    stamp.textContent = 'EMERGENCY: UNAUTHORIZED ACCESS';
  }
  
  // Force all particles to be red
  document.querySelectorAll('.particle').forEach(particle => {
    particle.style.backgroundColor = 'rgba(255, 0, 0, 0.6)';
    particle.style.boxShadow = '0 0 3px rgba(255, 0, 0, 0.6)';
  });
  
  // Force scanner line to be red
  const scanner = document.querySelector('.scanner-line');
  if (scanner) {
    scanner.style.background = 'linear-gradient(90deg, transparent, rgba(255, 0, 0, 0.8), transparent)';
    scanner.style.opacity = '0.8';
    scanner.style.height = '3px';
    scanner.style.animationDuration = '0.5s';
  }
  
  // Add audio alert
  speakNotification("Critical security breach detected. System lockdown initiated.");
  
  // Make the entire screen pulse red
  document.documentElement.classList.add('red-pulse');
  
  // Create lockout message if it doesn't exist
  if (!document.querySelector('.lockout-message')) {
    const lockoutMsg = document.createElement('div');
    lockoutMsg.className = 'lockout-message';
    lockoutMsg.innerHTML = '<span class="alert-prefix">ALERT:</span> SECURITY PROTOCOL ACTIVATED<span>System locked. Unauthorized access detected.</span><span class="small-text">Reload page to reset</span>';
    glassPanel.appendChild(lockoutMsg);
    
    // Force the lockout message to have the right colors
    lockoutMsg.style.color = '#ff3333';
    lockoutMsg.style.borderColor = '#990000';
    lockoutMsg.style.backgroundColor = 'rgba(0, 0, 0, 0.85)';
  }
}

// Reset function should also reset the filter and all inline styles
function resetSystem() {
  // Remove red filter
  document.documentElement.classList.remove('red-filter-active', 'red-pulse');
  
  // Remove the SVG filter
  document.querySelectorAll('#security-breach-filter').forEach(el => el.remove());
  document.documentElement.classList.remove('security-breach-active', 'total-breach');
  
  // Remove the breach overlay
  document.querySelector('.breach-overlay')?.remove();
  
  // Reset inline styles
  document.querySelectorAll('.btn-primary, input, .input-glow, .classified-stamp, .particle, .scanner-line').forEach(el => {
    el.removeAttribute('style');
  });
  
  // Reset flavor header
  glitchCoordinates('normal');
  const batchElement = document.querySelector('.header-batch');
  if (batchElement) {
    batchElement.classList.remove('warning-alert', 'danger-alert');
  }
  const phraseElement = document.querySelector('.header-phrase');
  if (phraseElement) {
    phraseElement.classList.remove('danger-text');
  }
}

// Initialize custom toggles
document.addEventListener('DOMContentLoaded', () => {
  setupCustomToggles();
});

function setupCustomToggles() {
  // Find all custom toggle containers
  const toggleContainers = document.querySelectorAll('.custom-toggle-container');
  
  toggleContainers.forEach(container => {
    const toggle = container.querySelector('.custom-toggle');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    
    // Set initial state based on input value
    if (hiddenInput && hiddenInput.value === "true") {
      toggle.classList.add('checked');
    }
    
    // Add click handler
    container.addEventListener('click', () => {
      // Toggle visual state
      toggle.classList.toggle('checked');
      
      // Update hidden input value for form submission
      if (hiddenInput) {
        hiddenInput.value = toggle.classList.contains('checked') ? "true" : "false";
      }
      
      // Trigger change event for any listeners
      const event = new Event('change', { bubbles: true });
      container.dispatchEvent(event);
    });
    
    // Make it keyboard accessible
    container.setAttribute('tabindex', '0');
    container.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        container.click();
      }
    });
  });
}

// Function to check if a custom toggle is checked
function isCustomToggleChecked(toggleId) {
  const toggle = document.querySelector(`#${toggleId}-toggle`);
  return toggle && toggle.classList.contains('checked');
}

// Initialize forms and handlers
document.addEventListener('DOMContentLoaded', () => {
  initForms();
  console.log('Init complete - form handlers ready');
});

function initForms() {
  // Find all forms
  const forms = document.querySelectorAll('form');
  
  forms.forEach(form => {
    // Add submission handler
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      
      // Log form submission
      const formType = this.dataset.formType || this.id || 'unknown';
      console.log(`${formType} form submitted`);
      
      // Handle registration form differently than login
      if (formType === 'register-form' || formType === 'register') {
        console.log('Registration form detected - launching aptitude tests');
        
        // Validate the form first
        let isValid = validateRegistrationForm(this);
        
        if (isValid) {
          // Launch aptitude test instead of showing success
          launchAptitudeTests();
        } else {
          console.log('Registration validation failed');
        }
        return; // Exit early for registration form
      }
      
      // Continue with normal validation and authentication for login form
      if (validateForm(this)) {
        console.log('Form validation successful');
        
        // Simulate successful submission
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.innerHTML = `<span>Processing...</span>`;
        }
        
        // For demo purposes - simulate success notification for login only
        setTimeout(() => {
          showNotification('Authentication successful', 'success');
          
          // For demonstration - redirect after successful login
          setTimeout(() => {
            // Only for login form
            showNotification('Redirecting to secure area...', 'notice');
          }, 2000);
        }, 1500);
      } else {
        console.log('Form validation failed');
      }
    });
  });
  
  // Add button click handler for init
  const initButton = document.querySelector('.btn-init');
  if (initButton) {
    initButton.addEventListener('click', () => {
      console.log('Init button clicked');
    });
  }
}

// Add a specific validation function for registration
function validateRegistrationForm(form) {
  let isValid = true;
  
  // Check required fields
  const nameInput = form.querySelector('#new-researcher-name');
  const emailInput = form.querySelector('#new-researcher-id');
  const passwordInput = form.querySelector('#clearance-code-new');
  
  if (nameInput && !nameInput.value.trim()) {
    flashInputError(nameInput);
    isValid = false;
  }
  
  if (emailInput && !emailInput.value.trim()) {
    flashInputError(emailInput);
    isValid = false;
  }
  
  if (passwordInput && !passwordInput.value.trim()) {
    flashInputError(passwordInput);
    isValid = false;
  }
  
  // Check protocol acceptance (using either checkbox or custom toggle)
  const protocolCheckbox = form.querySelector('.protocol-acceptance input[type="checkbox"]');
  const protocolToggle = form.querySelector('#protocol-checkbox-toggle');
  
  let protocolAccepted = false;
  
  if (protocolCheckbox && protocolCheckbox.checked) {
    protocolAccepted = true;
  } else if (protocolToggle && protocolToggle.classList.contains('checked')) {
    protocolAccepted = true;
  }
  
  if (!protocolAccepted) {
    // Flash error on the protocol acceptance container
    const container = form.querySelector('.protocol-acceptance');
    if (container) {
      container.classList.add('error-flash');
      setTimeout(() => {
        container.classList.remove('error-flash');
      }, 1000);
    }
    
    // Show specific error message
    showNotification('You must acknowledge the Field Protocol to continue', 'error');
    isValid = false;
  }
  
  return isValid;
}

// Make sure launchAptitudeTests is more robust
function launchAptitudeTests() {
  console.log("Launching aptitude tests");
  
  const aptitudeContainer = document.querySelector('.aptitude-test-container');
  if (!aptitudeContainer) {
    console.error("Aptitude test container not found!");
    // Create a fallback container if it doesn't exist
    createFallbackAptitudeContainer();
    return;
  }
  
  // Make sure it's visible in the DOM
  aptitudeContainer.style.display = 'flex';
  aptitudeContainer.classList.remove('hidden');
  
  // Force browser reflow
  void aptitudeContainer.offsetWidth;
  
  // Add active class for animations and visibility
  aptitudeContainer.classList.add('active');
  
  console.log("Aptitude container activated");
  
  // Initialize test functionality if available
  if (typeof initializeCurrentTest === 'function') {
    setTimeout(() => {
      try {
        console.log("Initializing first test");
        initializeCurrentTest(1);
        
        if (typeof setupTestNavigation === 'function') {
          setupTestNavigation();
        }
      } catch (err) {
        console.error("Error initializing tests:", err);
      }
    }, 100);
  } else {
    console.error("Test initialization function not found");
  }
}

// Create a fallback container if the aptitude test container is missing
function createFallbackAptitudeContainer() {
  const fallbackContainer = document.createElement('div');
  fallbackContainer.className = 'aptitude-test-container active';
  fallbackContainer.style.display = 'flex';
  
  fallbackContainer.innerHTML = `
    <div class="glass-panel aptitude-panel">
      <div class="test-header">
        <h2>Field Researcher Aptitude Assessment</h2>
        <div class="test-progress">
          <div class="progress-bar">
            <div class="progress-fill"></div>
          </div>
          <div class="progress-label">TEST 1/3</div>
        </div>
      </div>
      
      <div class="test-section active" data-test="1">
        <h3>Pattern Recognition Test</h3>
        <div class="test-instruction">
          Identify the next symbol in the sequence.
        </div>
        <div class="pattern-sequence">
          <div class="pattern-item">△</div>
          <div class="pattern-item">□</div>
          <div class="pattern-item">○</div>
          <div class="pattern-item">?</div>
        </div>
        <div class="pattern-options">
          <div class="pattern-option" data-correct="true">△</div>
          <div class="pattern-option">□</div>
          <div class="pattern-option">○</div>
          <div class="pattern-option">⬡</div>
        </div>
      </div>
      
      <div class="test-section" data-test="2">
        <h3>Memory Test</h3>
        <div class="test-instruction">
          Memorize the marker locations. You have <span class="countdown">5</span> seconds.
        </div>
        <div class="memory-map">
          <div class="memory-grid">
            <div class="memory-marker" style="top: 35%; left: 20%"></div>
            <div class="memory-marker" style="top: 15%; left: 60%"></div>
            <div class="memory-marker" style="top: 75%; left: 80%"></div>
          </div>
          <div class="map-overlay"></div>
        </div>
        <div class="memory-recall hidden">
          <div class="recall-grid">
            <div class="recall-cell" data-location="A1"></div>
            <div class="recall-cell" data-location="A2"></div>
            <div class="recall-cell" data-location="A3"></div>
            <div class="recall-cell" data-location="A4"></div>
            <div class="recall-cell" data-location="B1"></div>
            <div class="recall-cell" data-location="B2"></div>
            <div class="recall-cell" data-location="B3"></div>
            <div class="recall-cell" data-location="B4"></div>
            <div class="recall-cell" data-location="C1"></div>
            <div class="recall-cell" data-location="C2"></div>
            <div class="recall-cell" data-location="C3"></div>
            <div class="recall-cell" data-location="C4"></div>
            <div class="recall-cell" data-location="D1"></div>
            <div class="recall-cell" data-location="D2"></div>
            <div class="recall-cell" data-location="D3"></div>
            <div class="recall-cell" data-location="D4"></div>
          </div>
        </div>
      </div>
      
      <div class="test-navigation">
        <button type="button" class="btn-primary test-continue">
          <span class="btn-text">BEGIN ASSESSMENT</span>
          <div class="btn-glow"></div>
        </button>
      </div>
    </div>
  `;
  
  document.body.appendChild(fallbackContainer);
  console.log("Created fallback aptitude container with complete memory test");
  
  // Add styles for memory test
  const memoryStyles = document.createElement('style');
  memoryStyles.textContent = `
    .memory-map {
      position: relative;
      width: 100%;
      height: 200px;
      background: linear-gradient(135deg, var(--midnight-charcoal), var(--expedition-green));
      border: 1px solid var(--bioluminescent-teal);
      border-radius: 4px;
      margin-bottom: 20px;
    }
    .memory-grid {
      position: relative;
      width: 100%;
      height: 100%;
    }
    .memory-marker {
      position: absolute;
      width: 12px;
      height: 12px;
      background-color: var(--bioluminescent-teal);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      box-shadow: 0 0 8px var(--bioluminescent-teal);
      transition: opacity 0.3s ease;
    }
    .map-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(45, 48, 51, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--bioluminescent-teal);
      font-size: 1.5rem;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s ease;
    }
    .map-overlay.visible {
      opacity: 1;
    }
    .memory-recall {
      width: 100%;
      transition: opacity 0.5s ease;
    }
    .memory-recall.hidden {
      opacity: 0;
      pointer-events: none;
    }
    .recall-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      grid-template-rows: repeat(4, 1fr);
      gap: 4px;
      width: 100%;
      height: 200px;
    }
    .recall-cell {
      background-color: rgba(57, 240, 217, 0.1);
      border: 1px solid rgba(57, 240, 217, 0.3);
      border-radius: 2px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    .recall-cell:hover {
      background-color: rgba(57, 240, 217, 0.2);
    }
    .recall-cell.selected {
      background-color: rgba(57, 240, 217, 0.4);
      box-shadow: 0 0 5px var(--bioluminescent-teal);
    }
  `;
  document.head.appendChild(memoryStyles);
  
  // Setup the test navigation after a short delay to ensure DOM is ready
  setTimeout(() => {
    // Set up pattern test 
    const options = fallbackContainer.querySelectorAll('.pattern-option');
    options.forEach((option, index) => {
      option.addEventListener('click', () => {
        // Clear other selections
        options.forEach(o => o.classList.remove('selected'));
        
        // Set this option as selected
        option.classList.add('selected');
        
        // Debug info
        console.log(`Option ${index} selected: "${option.textContent.trim()}"`);
      });
    });
    
    // Setup test navigation
    setupTestNavigation();
  }, 100);
}

// Function to create a fallback logo SVG if needed
function createFallbackLogoSvg() {
  // Check if logo.svg exists by trying to fetch it
  fetch('logo.svg')
    .then(response => {
      if (!response.ok) {
        // Create a fallback SVG file
        const svgContent = `
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
            <circle cx="50" cy="50" r="40" fill="none" stroke="#39F0D9" stroke-width="3"/>
            <path d="M30 70 L50 30 L70 70 Z" fill="none" stroke="#39F0D9" stroke-width="2"/>
            <circle cx="50" cy="45" r="10" fill="#39F0D9" opacity="0.6"/>
          </svg>
        `;
        
        // Create a Blob and download link
        const blob = new Blob([svgContent], {type: 'image/svg+xml'});
        const dataUrl = URL.createObjectURL(blob);
        
        // Create a style that replaces all instances with the data URL
        const style = document.createElement('style');
        style.textContent = `
          .target-logo {
            background-image: url('${dataUrl}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
          }
        `;
        document.head.appendChild(style);
        
        console.log("Created fallback SVG logo");
      }
    })
    .catch(error => {
      console.log("Error checking for logo.svg, using fallback");
    });
}
</script>
</body>
</html>
