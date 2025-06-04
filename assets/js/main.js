/**
 * Main JavaScript file for the news website
 */

document.addEventListener('DOMContentLoaded', function() {
    // Bouton retour en haut
    const backToTopButton = document.querySelector('.back-to-top');
    
    if (backToTopButton) {
        // Afficher/masquer le bouton retour en haut en fonction de la position de défilement
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });
        
        // Défiler vers le haut lorsque le bouton est cliqué
        backToTopButton.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Bascule de la navigation mobile
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileNavToggle && mainNav) {
        mobileNavToggle.addEventListener('click', () => {
            mainNav.classList.toggle('show');
            mobileNavToggle.classList.toggle('active');
        });
    }
    
    // Animation du fil d'actualités en pause au survol
    const ticker = document.querySelector('.ticker');
    
    if (ticker) {
        ticker.addEventListener('mouseenter', () => {
            ticker.style.animationPlayState = 'paused';
        });
        
        ticker.addEventListener('mouseleave', () => {
            ticker.style.animationPlayState = 'running';
        });
        
        // Cloner les éléments du fil d'actualités pour une boucle continue
        const tickerItems = document.querySelectorAll('.ticker-item');
        if (tickerItems.length > 0) {
            tickerItems.forEach(item => {
                const clone = item.cloneNode(true);
                ticker.appendChild(clone);
            });
        }
    }
    
    // Chargement paresseux des images
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Solution de repli pour les navigateurs sans IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
    
    // Initialisation des infobulles Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Effet de zoom sur les images d'articles
    const articleImages = document.querySelectorAll('.article-image');
    
    articleImages.forEach(img => {
        img.addEventListener('click', () => {
            const modal = document.createElement('div');
            modal.classList.add('image-modal');
            modal.innerHTML = `
                <div class="modal-content">
                    <img src="${img.src}" alt="${img.alt}">
                    <button class="close-modal">&times;</button>
                </div>
            `;
            document.body.appendChild(modal);
            
            modal.querySelector('.close-modal').addEventListener('click', () => {
                modal.remove();
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        });
    });
    
    // Validation du formulaire de recherche
    const searchForm = document.querySelector('.search-form');
    
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            const searchInput = searchForm.querySelector('input[name="q"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
});