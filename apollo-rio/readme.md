Apollo::Rio - Páginas Modelos Personalizadas de e para Apollo::rio

📁 ESTRUTURA DE ARQUIVOS E PASTAS

apollo-rio/
├── apollo-rio.php                          # Main plugin file
├── includes/
│   ├── class-pwa-page-builders.php         # Main class (Artifact 1)
│   ├── template-functions.php              # Helper functions
│   └── admin-settings.php                  # Admin panel for settings
├── templates/
│   ├── pagx\_site.php                       # Builder 1: Site::rio
│   ├── pagx\_app.php                        # Builder 2: App::rio
│   ├── pagx\_appclean.php                   # Builder 3: App::rio clean
│   └── partials/
│       ├── header.php                      # Full header with nav
│       ├── header-minimal.php              # Minimal header (no nav)
│       ├── footer.php                      # Full footer with widgets
│       └── footer-minimal.php              # Minimal footer
├── assets/
│   ├── js/
│   │   └── pwa-detect.js                   # PWA detection script
│   └── css/
│       └── pwa-templates.css               # All template styles
└── manifest.json                           # PWA manifest (root level)



1️⃣ pagx\_site - Site::rio
Modelo de página que:

* Header e footer completos;
* Carregado completo só em PC e Mobile (browser e PWA);
* Sem PWA redirecionamentos;
* SEO-friendly páginas.



2️⃣ pagx\_app - App::rio
Modelo de página que:

* Header e footer completos;
* Carregado completo somente no PC e PWA;
* Mobile veririfica se no PWA carrega normalmente, caso contrário instrução para ter app.



3️⃣ pagx\_appclean - App::rio clean
Modelo de página que:

* Nada de header e footer;
* Carregado completo somente no PC e PWA;
* Mobile veririfica se no PWA carrega normalmente, caso contrário instrução para ter app.



🚀 USAGE GUIDE
Creating a Page with Page Builder



Go to: Pages → Add New


Page Attributes → Template:



Select "Site::rio" (always shows content)
Select "App::rio" (PWA required for mobile)
Select "App::rio clean" (PWA required, minimal UI)



Add Content: Use WordPress editor or Elementor


Publish

