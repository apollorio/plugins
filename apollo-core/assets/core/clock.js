/**
 * Apollo Clock Display
 * STRICT MODE: DOMContentLoaded wrapper with null checks.
 *
 * Usage in HTML:
 *   <span id="agoraH"></span>   - Full time: HH:MM:SS
 *   <span id="agoraHH"></span>  - Short time: HH:MM
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	var fullTimeEl = document.getElementById('agoraH');
	var shortTimeEl = document.getElementById('agoraHH');

	// Strict early return if no clock elements exist
	if (!fullTimeEl && !shortTimeEl) {
		return;
	}

	function updateTime() {
		var now = new Date();
		var hours = String(now.getHours()).padStart(2, '0');
		var minutes = String(now.getMinutes()).padStart(2, '0');
		var seconds = String(now.getSeconds()).padStart(2, '0');

		// Full time with seconds (24-hour)
		if (fullTimeEl) {
			fullTimeEl.textContent = hours + ':' + minutes + ':' + seconds;
		}

		// Short time (HH:MM only)
		if (shortTimeEl) {
			shortTimeEl.textContent = hours + ':' + minutes;
		}
	}

	// Initial update
	updateTime();

	// Update every second
	setInterval(updateTime, 1000);
});
