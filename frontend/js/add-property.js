        (function() {
            // ----- DOM refs -----
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const tabLogin = document.getElementById('tabLogin');
            const tabRegister = document.getElementById('tabRegister');
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');

            // show message if exists
            function showMessage(text, type) {
                if (!text) {
                    messageBox.classList.add('hidden');
                    return;
                }
                messageBox.classList.remove('hidden', 'error', 'success');
                if (type === 'error') messageBox.classList.add('error');
                else if (type === 'success') messageBox.classList.add('success');
                messageText.textContent = text;
            }

            // Display initial message if exists
            if (msgData && msgData.text) {
                showMessage(msgData.text, msgData.type);
            }

            // ----- tab switching -----
            function setActiveTab(tab) {
                if (tab === 'login') {
                    loginForm.classList.remove('hidden');
                    registerForm.classList.add('hidden');
                    tabLogin.classList.add('active');
                    tabRegister.classList.remove('active');
                } else {
                    loginForm.classList.add('hidden');
                    registerForm.classList.remove('hidden');
                    tabRegister.classList.add('active');
                    tabLogin.classList.remove('active');
                }
                // clear any message when switching tab
                showMessage('', '');
                // clear form fields
                document.querySelectorAll('form input').forEach(inp => inp.value = '');
            }

            // Tab click handlers
            tabLogin.addEventListener('click', function(e) {
                e.preventDefault();
                setActiveTab('login');
            });

            tabRegister.addEventListener('click', function(e) {
                e.preventDefault();
                setActiveTab('register');
            });

            // Handle auto-switch to login after successful registration
            if (msgData && msgData.text && msgData.type === 'success' && msgData.text.includes('Registration successful')) {
                setTimeout(function() {
                    setActiveTab('login');
                    showMessage(msgData.text, msgData.type);
                }, 600);
            }

            // Handle error messages - switch to appropriate tab
            if (msgData && msgData.text) {
                if (msgData.type === 'error') {
                    const lowerMsg = msgData.text.toLowerCase();
                    if (lowerMsg.includes('register') || lowerMsg.includes('email already') || lowerMsg.includes('password must')) {
                        setActiveTab('register');
                        showMessage(msgData.text, msgData.type);
                    } else if (lowerMsg.includes('invalid email') || lowerMsg.includes('login') || lowerMsg.includes('fill in')) {
                        setActiveTab('login');
                        showMessage(msgData.text, msgData.type);
                    } else {
                        setActiveTab('login');
                        showMessage(msgData.text, msgData.type);
                    }
                } else if (msgData.type === 'success' && !msgData.text.includes('Registration successful')) {
                    setActiveTab('login');
                    showMessage(msgData.text, msgData.type);
                }
            }

            // Default to login tab if no message
            if (!msgData || !msgData.text) {
                setActiveTab('login');
            }

        })();
    
