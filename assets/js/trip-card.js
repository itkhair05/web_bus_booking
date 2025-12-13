/**
 * TRIP CARD TEMPLATE - JAVASCRIPT HELPERS
 * Xử lý interactions cho trip card component
 */

/**
 * Toggle trip details (collapsible)
 * @param {number} tripId - ID của chuyến xe
 */
function toggleTripDetails(tripId) {
    const detailsDiv = document.getElementById('trip-details-' + tripId);
    const btn = event.target.closest('.btn-details');
    
    if (!detailsDiv || !btn) return;
    
    // Toggle display
    if (detailsDiv.style.display === 'none' || !detailsDiv.style.display) {
        // Show details
        detailsDiv.style.display = 'block';
        btn.innerHTML = 'Thu gọn <i class="fas fa-chevron-up"></i>';
        btn.classList.add('active');
        
        // Smooth scroll to details
        setTimeout(() => {
            detailsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    } else {
        // Hide details
        detailsDiv.style.display = 'none';
        btn.innerHTML = 'Thông tin chi tiết <i class="fas fa-chevron-down"></i>';
        btn.classList.remove('active');
    }
}

/**
 * Filter trips by criteria
 * @param {string} criteria - Tiêu chí lọc (price, time, rating)
 */
function filterTrips(criteria) {
    const tripsContainer = document.querySelector('.trips-container');
    const tripCards = Array.from(tripsContainer.querySelectorAll('.trip-card'));
    
    tripCards.sort((a, b) => {
        switch(criteria) {
            case 'price-low':
                return getPrice(a) - getPrice(b);
            case 'price-high':
                return getPrice(b) - getPrice(a);
            case 'time-early':
                return getTime(a) - getTime(b);
            case 'time-late':
                return getTime(b) - getTime(a);
            case 'rating':
                return getRating(b) - getRating(a);
            default:
                return 0;
        }
    });
    
    // Re-append sorted cards
    tripCards.forEach(card => tripsContainer.appendChild(card));
    
    // Add animation
    tripCards.forEach((card, index) => {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.transition = 'opacity 0.3s ease';
            card.style.opacity = '1';
        }, index * 50);
    });
}

/**
 * Get price from trip card
 */
function getPrice(card) {
    const priceText = card.querySelector('.current-price').textContent;
    return parseInt(priceText.replace(/[^\d]/g, ''));
}

/**
 * Get departure time from trip card
 */
function getTime(card) {
    const timeText = card.querySelector('.timeline-point .time').textContent;
    const [hours, minutes] = timeText.split(':').map(Number);
    return hours * 60 + minutes;
}

/**
 * Get rating from trip card
 */
function getRating(card) {
    const ratingBadge = card.querySelector('.rating-badge');
    if (!ratingBadge) return 0;
    const ratingText = ratingBadge.textContent.match(/[\d.]+/);
    return ratingText ? parseFloat(ratingText[0]) : 0;
}

/**
 * Search/Filter trips by text
 * @param {string} searchText - Text to search
 */
function searchTrips(searchText) {
    const tripCards = document.querySelectorAll('.trip-card');
    const searchLower = searchText.toLowerCase();
    let visibleCount = 0;
    
    tripCards.forEach(card => {
        const partnerName = card.querySelector('.partner-name').textContent.toLowerCase();
        const busType = card.querySelector('.bus-type').textContent.toLowerCase();
        const stations = card.querySelectorAll('.station');
        const stationText = Array.from(stations).map(s => s.textContent.toLowerCase()).join(' ');
        
        const isMatch = partnerName.includes(searchLower) || 
                       busType.includes(searchLower) || 
                       stationText.includes(searchLower);
        
        if (isMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    updateNoResultsMessage(visibleCount);
}

/**
 * Update no results message
 */
function updateNoResultsMessage(count) {
    let noResultsMsg = document.querySelector('.no-results');
    
    if (count === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('p');
            noResultsMsg.className = 'no-results';
            noResultsMsg.textContent = 'Không tìm thấy chuyến xe phù hợp';
            document.querySelector('.trips-container').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else {
        if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
}

/**
 * Filter trips by price range
 * @param {number} minPrice - Giá tối thiểu
 * @param {number} maxPrice - Giá tối đa
 */
function filterByPriceRange(minPrice, maxPrice) {
    const tripCards = document.querySelectorAll('.trip-card');
    let visibleCount = 0;
    
    tripCards.forEach(card => {
        const price = getPrice(card);
        const isInRange = price >= minPrice && price <= maxPrice;
        
        card.style.display = isInRange ? 'block' : 'none';
        if (isInRange) visibleCount++;
    });
    
    updateNoResultsMessage(visibleCount);
}

/**
 * Filter trips by time range
 * @param {string} timeRange - 'morning', 'afternoon', 'evening', 'night'
 */
function filterByTimeRange(timeRange) {
    const tripCards = document.querySelectorAll('.trip-card');
    let visibleCount = 0;
    
    const ranges = {
        'morning': [0, 720],      // 00:00 - 12:00
        'afternoon': [720, 1080], // 12:00 - 18:00
        'evening': [1080, 1320],  // 18:00 - 22:00
        'night': [1320, 1440]     // 22:00 - 24:00
    };
    
    const [min, max] = ranges[timeRange] || [0, 1440];
    
    tripCards.forEach(card => {
        const time = getTime(card);
        const isInRange = time >= min && time < max;
        
        card.style.display = isInRange ? 'block' : 'none';
        if (isInRange) visibleCount++;
    });
    
    updateNoResultsMessage(visibleCount);
}

/**
 * Filter trips by amenities
 * @param {Array} amenities - ['wifi', 'ac', 'wc']
 */
function filterByAmenities(amenities) {
    const tripCards = document.querySelectorAll('.trip-card');
    let visibleCount = 0;
    
    tripCards.forEach(card => {
        const cardAmenities = Array.from(card.querySelectorAll('.amenity-badge'))
            .map(badge => badge.textContent.toLowerCase());
        
        const hasAllAmenities = amenities.every(amenity => 
            cardAmenities.some(cardAmenity => cardAmenity.includes(amenity))
        );
        
        card.style.display = hasAllAmenities ? 'block' : 'none';
        if (hasAllAmenities) visibleCount++;
    });
    
    updateNoResultsMessage(visibleCount);
}

/**
 * Reset all filters
 */
function resetFilters() {
    const tripCards = document.querySelectorAll('.trip-card');
    tripCards.forEach(card => {
        card.style.display = 'block';
    });
    
    const noResultsMsg = document.querySelector('.no-results');
    if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
}

/**
 * Count trips by criteria
 */
function countTrips() {
    const tripCards = document.querySelectorAll('.trip-card:not([style*="display: none"])');
    return tripCards.length;
}

/**
 * Highlight trip card (e.g., after booking)
 * @param {number} tripId
 */
function highlightTrip(tripId) {
    const card = document.querySelector(`.trip-card[data-trip-id="${tripId}"]`);
    if (!card) return;
    
    card.style.border = '2px solid #3b82f6';
    card.style.boxShadow = '0 8px 24px rgba(59, 130, 246, 0.3)';
    
    // Scroll to card
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Remove highlight after 3 seconds
    setTimeout(() => {
        card.style.border = '';
        card.style.boxShadow = '';
    }, 3000);
}

/**
 * Add to favorites (example)
 * @param {number} tripId
 */
function addToFavorites(tripId) {
    // TODO: Implement API call to add to favorites
    console.log('Added trip', tripId, 'to favorites');
    
    // Show notification
    showNotification('Đã thêm vào yêu thích', 'success');
}

/**
 * Compare trips (example)
 * @param {Array} tripIds
 */
function compareTrips(tripIds) {
    if (tripIds.length < 2) {
        showNotification('Vui lòng chọn ít nhất 2 chuyến để so sánh', 'warning');
        return;
    }
    
    // TODO: Implement comparison modal
    console.log('Comparing trips:', tripIds);
}

/**
 * Show notification toast
 * @param {string} message
 * @param {string} type - 'success', 'error', 'warning', 'info'
 */
function showNotification(message, type = 'info') {
    // Simple toast notification (can be improved with library)
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'};
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        toggleTripDetails,
        filterTrips,
        searchTrips,
        filterByPriceRange,
        filterByTimeRange,
        filterByAmenities,
        resetFilters,
        countTrips,
        highlightTrip,
        addToFavorites,
        compareTrips
    };
}

