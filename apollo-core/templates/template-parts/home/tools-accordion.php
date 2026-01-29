<?php
/**
 * Template Part: Tools Accordion
 * Description: "DJ Global Roster" and Tools Accordion section.
 * Location: template-parts/home/tools-accordion.php
 */

defined( 'ABSPATH' ) || exit;

// Configuration for the accordion items
$tools_data = array(
    array(
        'title'       => "Plano, apollo's image creative studio",
        'description' => 'Estúdio criativo para produção de material visual. Crie flyers, capas e identidades visuais para seus eventos e releases.',
        'images'      => array(
            'https://apollo.rio.br/v2/pln1.png',
            'https://apollo.rio.br/v2/pln2.png'
        ),
        'iframe'      => 'https://plano.apollo.rio.br/ij.html',
        'hide_media'  => false, // Visible
    ),
    array(
        'title'       => "Doc & Assina::rio, seu contrato fácil",
        'description' => 'Contratos digitais simplificados para a indústria criativa. Assinatura eletrônica válida juridicamente.',
        'images'      => array(
            'https://apollo.rio.br/v2/ass1.png',
            'https://apollo.rio.br/v2/ass2.png'
        ),
        'iframe'      => 'https://apollo.rio.br/v2/sign.html',
        'hide_media'  => false, // Visible
    ),
    array(
        'title'       => "Cena::rio, ferramentas da indústria cultural",
        'description' => 'Suite completa de ferramentas para produtores, promoters e artistas. Gestão de eventos, bilheteria e analytics.',
        'images'      => array(
            'https://images.unsplash.com/photo-1514525253440-b393452e8d2e?q=80&w=600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=600&auto=format&fit=crop'
        ),
        'iframe'      => 'https://plano.apollo.rio.br/ij.html',
        'hide_media'  => true, // Hidden via style="display:none"
    ),
    array(
        'title'       => "Repasses de Ingressos & Acomodações",
        'description' => 'Marketplace seguro para revenda de ingressos e hospedagem compartilhada durante eventos.',
        'images'      => array(
            'https://images.unsplash.com/photo-1540541338287-41700207dee6?q=80&w=600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?q=80&w=600&auto=format&fit=crop'
        ),
        'iframe'      => 'https://plano.apollo.rio.br/ij.html',
        'hide_media'  => true, // Hidden via style="display:none"
    ),
);
?>

<section id="roster" class="tools-section container">
    <div class="tools-grid">
        
        <div class="tools-intro reveal-up">
            <div class="hub-status" style="margin-bottom:16px">
                <span class="hub-pulse"></span>
                <span class="hub-status-text"><?php esc_html_e( 'Ferramenta', 'apollo-core' ); ?></span>
            </div>
            <h2><?php esc_html_e( 'DJ Global Roster', 'apollo-core' ); ?></h2>
            <p class="card-text">
                <?php esc_html_e( 'Conectando sons locais a plataformas globais. Curadoria internacional sem intermediários.', 'apollo-core' ); ?>
            </p>
        </div>

        <div class="reveal-up delay-100">
            <h3 class="section-label" style="margin-bottom:24px"><?php esc_html_e( 'Ferramentas', 'apollo-core' ); ?></h3>
            
            <div class="accordion">
                <?php foreach ( $tools_data as $index => $item ) : 
                    // Determine visibility style based on the 'hide_media' flag
                    $media_style = $item['hide_media'] ? 'style="display:none"' : '';
                ?>
                    <div class="accordion-item">
                        <button class="accordion-trigger" type="button" aria-expanded="false">
                            <span class="accordion-title"><?php echo esc_html( $item['title'] ); ?></span>
                            <span class="accordion-icon"></span>
                        </button>
                        
                        <div class="accordion-content">
                            <div class="accordion-inner">
                                <p><?php echo esc_html( $item['description'] ); ?></p>
                                
                                <?php if ( ! empty( $item['images'] ) ) : ?>
                                    <div class="accordion-images">
                                        <?php foreach ( $item['images'] as $img_url ) : ?>
                                            <img src="<?php echo esc_url( $img_url ); ?>" 
                                                 alt="<?php echo esc_attr( $item['title'] ); ?>" 
                                                 <?php echo $media_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $item['iframe'] ) ) : ?>
                                    <div class="accordion-iframe">
                                        <iframe src="<?php echo esc_url( $item['iframe'] ); ?>" 
                                                allowfullscreen 
                                                <?php echo $media_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                        </iframe>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<script>
(function() {
    var triggers = document.querySelectorAll('.accordion-trigger');
    
    triggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var item = this.parentElement;
            var isActive = item.classList.contains('active');
            
            // Close all other items
            document.querySelectorAll('.accordion-item').forEach(function(i) {
                i.classList.remove('active');
                var btn = i.querySelector('.accordion-trigger');
                if(btn) btn.setAttribute('aria-expanded', 'false');
            });
            
            // Toggle current item
            if (!isActive) {
                item.classList.add('active');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });
})();
</script>