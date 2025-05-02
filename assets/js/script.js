// Ana JavaScript Dosyası
document.addEventListener('DOMContentLoaded', function() {
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
                const textarea = document.createElement('textarea');
                textarea.value = textToCopy;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                // Kopya bildirimini göster
                this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
                }, 2000);
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