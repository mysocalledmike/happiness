// Main JavaScript file for shared functionality

// Utility function for AJAX requests
function request(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const config = { ...defaults, ...options };
    
    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }
    
    return fetch(url, config)
        .then(response => response.json())
        .catch(error => {
            console.error('Request error:', error);
            throw error;
        });
}

// Auto-save functionality for forms
function setupAutoSave(formSelector, saveUrl, options = {}) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    
    const defaults = {
        delay: 1000, // 1 second delay
        messageSelector: '.save-message',
        fields: 'input, textarea, select'
    };
    
    const config = { ...defaults, ...options };
    let saveTimeout;
    let messageElement = document.querySelector(config.messageSelector);
    
    // Create message element if it doesn't exist
    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.className = config.messageSelector.replace('.', '');
        form.insertBefore(messageElement, form.firstChild);
    }
    
    function showSaveMessage(text, type = 'success') {
        messageElement.textContent = text;
        messageElement.className = config.messageSelector.replace('.', '') + ' ' + type;
        messageElement.style.display = 'block';
        
        setTimeout(() => {
            messageElement.style.display = 'none';
        }, 3000);
    }
    
    function saveData() {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        request(saveUrl, {
            method: 'POST',
            body: data
        })
        .then(response => {
            if (response.success) {
                showSaveMessage('✓ Saved', 'success');
            } else {
                showSaveMessage('Save failed: ' + response.message, 'error');
            }
        })
        .catch(error => {
            showSaveMessage('Save failed', 'error');
        });
    }
    
    // Add event listeners to form fields
    form.querySelectorAll(config.fields).forEach(field => {
        field.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveData, config.delay);
        });
        
        field.addEventListener('blur', () => {
            clearTimeout(saveTimeout);
            saveData();
        });
    });
}

// Utility function to show loading state
function setLoadingState(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.textContent = 'Loading...';
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || button.textContent;
    }
}