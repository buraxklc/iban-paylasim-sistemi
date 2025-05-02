<!-- includes/footer.php -->
</div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('wrapper').classList.toggle('toggled');
                });
            }
            
            // Mobil cihazlarda hamburger'a tıklanınca sidebar'ı kapat
            const sidebarLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item');
            if (window.innerWidth < 768) {
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        document.getElementById('wrapper').classList.add('toggled');
                    });
                });
            }
            
            // IBAN formatını doğrulama
            const ibanInputs = document.querySelectorAll('.iban-input');
            if (ibanInputs) {
                ibanInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        this.value = this.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
                        
                        // Boşluk ekle (TR12 3456 ...)
                        if (this.value.length > 4) {
                            let formattedValue = '';
                            for (let i = 0; i < this.value.length; i++) {
                                if (i > 0 && i % 4 === 0) {
                                    formattedValue += ' ';
                                }
                                formattedValue += this.value[i];
                            }
                            this.value = formattedValue;
                        }
                    });
                });
            }

            // Kopyalama butonu işlevselliği
            const copyButtons = document.querySelectorAll('.copy-btn');
            if (copyButtons) {
                copyButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const textToCopy = this.getAttribute('data-copy');
                        navigator.clipboard.writeText(textToCopy).then(() => {
                            // Kopyalama başarılı
                            this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
                            setTimeout(() => {
                                this.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
                            }, 2000);
                        }).catch(err => {
                            // Alternatif kopyalama metodu
                            const textarea = document.createElement('textarea');
                            textarea.value = textToCopy;
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                            
                            this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
                            setTimeout(() => {
                                this.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
                            }, 2000);
                        });
                    });
                });
            }

            // Form doğrulama
            const forms = document.querySelectorAll('.needs-validation');
            if (forms) {
                Array.from(forms).forEach(form => {
                    form.addEventListener('submit', event => {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }
        });
    </script>
</body>
</html>