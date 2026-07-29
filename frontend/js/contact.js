// frontend/js/contact.js
// Hinahawakan ang pag-submit ng "Send Us a Message" form sa contact.php

(function () {
    const form = document.getElementById('contactForm');
    const toast = document.getElementById('toast');

    function showToast(message) {
        if (!toast) {
            alert(message);
            return;
        }
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3500);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('.btn-send');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            const payload = {
                first_name: document.getElementById('firstName').value.trim(),
                last_name: document.getElementById('lastName').value.trim(),
                email: document.getElementById('emailInput').value.trim(),
                message: document.getElementById('messageInput').value.trim(),
            };

            fetch('api/send_contact_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Message sent!');
                    form.reset();
                } else {
                    showToast(data.error || 'Something went wrong. Please try again.');
                }
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            })
            .catch(() => {
                showToast('Something went wrong. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }

    // ===== Mobile nav toggle (hamburger menu) =====
    const burgerBtn = document.getElementById('burgerBtn');
    const nav = document.getElementById('nav');
    if (burgerBtn && nav) {
        burgerBtn.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }
})();