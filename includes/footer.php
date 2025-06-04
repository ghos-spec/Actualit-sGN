<!-- Footer -->
    <footer class="site-footer">
        <div class="footer-main">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget about-widget">
                            <h3 class="widget-title">À propos</h3>
                            <div class="about-content">
                                <div class="footer-logo">
                                    <h2 class="site-title">Actualités<span>GN</span></h2>
                                </div>
                                <p>Votre source d'informations fiable au Gabon. Nous couvrons l'actualité politique, économique, sportive et culturelle avec rigueur et professionnalisme.</p>
                                <div class="social-links">
                                    <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                                    <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                                    <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget links-widget">
                            <h3 class="widget-title">Catégories</h3>
                            <ul class="footer-links">
                                <?php
                                // Get all categories for footer links
                                $footerCategories = getAllCategories($conn);
                                foreach ($footerCategories as $category):
                                ?>
                                <li>
                                    <a href="category.php?slug=<?= htmlspecialchars($category['slug']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget contact-widget">
                            <h3 class="widget-title">Contact</h3>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="bi bi-geo-alt"></i>
                                    <p>123 Boulevard des Médias, Libreville, Gabon</p>
                                </div>
                                <div class="contact-item">
                                    <i class="bi bi-telephone"></i>
                                    <p>+241 12 345 678</p>
                                </div>
                                <div class="contact-item">
                                    <i class="bi bi-envelope"></i>
                                    <p>contact@actualitesgn.com</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="copyright">
                            &copy; <?= date('Y') ?> Actualités Gabonaises. Tous droits réservés.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="footer-menu">
                            <ul>
                                <li><a href="#">Mentions légales</a></li>
                                <li><a href="#">Politique de confidentialité</a></li>
                                <li><a href="#">Conditions d'utilisation</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top" id="backToTop"><i class="bi bi-arrow-up"></i></a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>