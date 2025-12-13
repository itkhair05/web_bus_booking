/**
 * Main JavaScript - VeXeRe Style
 * Các functions chung cho ứng dụng
 */

// Get CSRF token from meta tag
function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

// Format date
function formatDate(dateString, format = 'dd/mm/yyyy') {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    if (format === 'dd/mm/yyyy') {
        return `${day}/${month}/${year}`;
    }
    return dateString;
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// AJAX request helper
async function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Request error:', error);
        return { success: false, error: 'Đã có lỗi xảy ra' };
    }
}

// Autocomplete for locations
function initLocationAutocomplete(inputId, suggestions) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const dropdown = document.createElement('div');
    dropdown.className = 'autocomplete-dropdown';
    input.parentElement.appendChild(dropdown);
    
    input.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        dropdown.innerHTML = '';
        
        if (value.length < 2) {
            dropdown.style.display = 'none';
            return;
        }
        
        const filtered = suggestions.filter(s => 
            s.toLowerCase().includes(value)
        );
        
        if (filtered.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        filtered.forEach(item => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.textContent = item;
            div.addEventListener('click', function() {
                input.value = item;
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(div);
        });
        
        dropdown.style.display = 'block';
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== input) {
            dropdown.style.display = 'none';
        }
    });
}

// Initialize date picker
function initDatePicker(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    input.setAttribute('min', today);
    
    // Set default value to today
    if (!input.value) {
        input.value = today;
    }
}

// Swap locations
function swapLocations() {
    const fromInput = document.getElementById('from_location');
    const toInput = document.getElementById('to_location');
    
    if (fromInput && toInput) {
        const temp = fromInput.value;
        fromInput.value = toInput.value;
        toInput.value = temp;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Common locations in Vietnam
    const vietnamCities = [
        'Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
        'Nha Trang', 'Đà Lạt', 'Vũng Tàu', 'Hội An', 'Huế',
        'Quy Nhơn', 'Phú Quốc', 'Phan Thiết', 'Hạ Long', 'Sapa'
    ];
    
    // Initialize location autocomplete
    initLocationAutocomplete('from_location', vietnamCities);
    initLocationAutocomplete('to_location', vietnamCities);
    
    // Initialize date picker
    initDatePicker('departure_date');
    
    // Add swap button listener
    const swapBtn = document.querySelector('.swap-locations');
    if (swapBtn) {
        swapBtn.addEventListener('click', swapLocations);
    }
});

