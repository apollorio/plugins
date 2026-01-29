<?php

namespace Apollo\Modules\Chat\Gateways;

use Apollo\Contracts\ChatGateway;

/**
 * Realtime Gateway (stub)
 */
class RealtimeGateway implements ChatGateway {

	public function sendMessage( $channel, $message, $user ) {
		// TODO: send real-time message
	}

	public function getHistory( $channel, $limit = 50 ) {
		// TODO: get chat history
	}

	public function joinChannel( $channel, $user ) {
		// TODO: join user to chat channel
	}
}
