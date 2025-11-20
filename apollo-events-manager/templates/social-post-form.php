<?php
/**
 * Social Post Form Template
 * TODO 110-114: Apollo Social Post Form with character limit and validation
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// TODO 110: Post form
// TODO 111: 281 character limit
// TODO 112: Animated counter
// TODO 113: Real-time validation
// TODO 114: Submit animation

?>
<form class="apollo-social-post-form" data-apollo-form="true" data-char-counter="281">
    <div class="form-group">
        <textarea 
            id="social-post-content" 
            name="post_content" 
            class="apollo-textarea" 
            maxlength="281" 
            placeholder="O que está acontecendo?"
            data-validate="true"
            required></textarea>
        
        <!-- TODO 112: Animated counter -->
        <div class="char-counter" data-char-counter-display="true">
            <span class="char-count">0</span>/<span class="char-limit">281</span>
        </div>
        
        <!-- TODO 113: Validation messages -->
        <div class="validation-message" data-validation-message="true"></div>
    </div>
    
    <div class="form-actions">
        <button type="button" class="btn-media" data-action="upload-media">
            <i class="ri-image-line"></i>
        </button>
        
        <!-- TODO 114: Submit with animation -->
        <button type="submit" class="btn-submit" data-submit-animation="true">
            <span class="btn-text">Publicar</span>
            <span class="btn-loader" style="display: none;">
                <i class="ri-loader-4-line"></i>
            </span>
        </button>
    </div>
</form>

