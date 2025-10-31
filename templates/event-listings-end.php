            </div>

            <!-- Bottom Action Buttons -->
            <div class="wpem-event-listings-footer">
                <div class="wpem-bottom-actions">
                    <button id="bottomShareBtn" class="wpem-btn wpem-btn-secondary">
                        <i class="wpem-icon-share"></i>
                        <?php _e('Compartilhar', 'wp-event-manager'); ?>
                    </button>

                    <button id="bottomTicketBtn" class="wpem-btn wpem-btn-primary">
                        <i class="wpem-icon-ticket"></i>
                        <span id="changingword">Ingressos</span>
                    </button>
                </div>

                <!-- Promo Code Section -->
                <div class="wpem-promo-section">
                    <div class="promo-code-display">
                        <span><?php _e('Use o código', 'wp-event-manager'); ?>:</span>
                        <strong>APOLLO</strong>
                        <button class="copy-code-mini" onclick="copyPromoCode()">
                            <i class="wpem-icon-copy"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<?php do_action('event_manager_pagination'); ?>

<?php
/**
 * Template for event listings end wrapper
 * This template wraps the end of the event listings
 */