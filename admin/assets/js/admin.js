/**
 * Admin Panel JavaScript
 */

// Initialize when document is ready
$(document).ready(function() {
    // Toggle sidebar menu
    $('.sidebar-toggle').on('click', function() {
        $('body').toggleClass('sidebar-collapse');
    });
    
    // Initialize Summernote editor if present
    if ($('.summernote').length) {
        $('.summernote').summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    // Custom image upload logic here if needed
                    for (let i = 0; i < files.length; i++) {
                        uploadImage(files[i], this);
                    }
                }
            }
        });
    }
    
    // Function to upload images from Summernote
    function uploadImage(file, editor) {
        let formData = new FormData();
        formData.append('file', file);
        
        $.ajax({
            url: 'upload_image.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(url) {
                $(editor).summernote('insertImage', url);
            },
            error: function() {
                alert('Erreur lors du téléchargement de l\'image');
            }
        });
    }
    
    // Toggle submenus in sidebar
    $('.nav-item.has-treeview > a').on('click', function(e) {
        e.preventDefault();
        $(this).parent().toggleClass('menu-open');
        $(this).next('.nav-treeview').slideToggle();
    });
    
    // Set active menu item based on current page
    const currentPath = window.location.pathname;
    $('.nav-item a').each(function() {
        if ($(this).attr('href') === currentPath) {
            $(this).addClass('active');
            
            // If it's in a submenu, open the parent menu
            if ($(this).closest('.nav-treeview').length) {
                $(this).closest('.nav-item').addClass('menu-open');
                $(this).closest('.nav-treeview').show();
            }
        }
    });
    
    // Initialize data tables if present
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
    
    // Form validation for required fields
    $('form').on('submit', function() {
        let isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        return isValid;
    });
    
    // File input preview
    $('input[type="file"]').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
    
    // Image preview
    $('input[type="file"][accept*="image"]').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $(this).closest('form').find('.image-preview');
                if (preview.length) {
                    preview.attr('src', e.target.result);
                    preview.show();
                }
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });
    
    // Confirm delete
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            e.preventDefault();
        }
    });
    
    // Back to top button
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn();
        } else {
            $('.back-to-top').fadeOut();
        }
    });
});