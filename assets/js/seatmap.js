/**
 * Seat Map JavaScript
 * Quản lý chọn ghế cho chuyến xe
 */

class SeatMap {
    constructor(tripId, totalSeats, layout = '2-2', bookedSeats = []) {
        this.tripId = tripId;
        this.totalSeats = totalSeats;
        this.layout = layout;
        this.bookedSeats = bookedSeats;
        this.selectedSeats = [];
        this.maxSeats = 5; // Tối đa 5 ghế/lần đặt
        this.pricePerSeat = 0;
        
        this.init();
    }
    
    init() {
        this.renderSeatMap();
        this.attachEventListeners();
        this.updateSummary();
    }
    
    renderSeatMap() {
        const container = document.getElementById('seats-container');
        if (!container) return;
        
        const [leftSeats, rightSeats] = this.layout.split('-').map(Number);
        const seatsPerRow = leftSeats + rightSeats;
        const totalRows = Math.ceil(this.totalSeats / seatsPerRow);
        
        let html = '';
        let seatNumber = 1;
        
        for (let row = 1; row <= totalRows; row++) {
            html += '<div class="seat-row">';
            
            // Left side seats
            for (let i = 0; i < leftSeats && seatNumber <= this.totalSeats; i++) {
                html += this.renderSeat(seatNumber, row, i + 1);
                seatNumber++;
            }
            
            // Aisle
            html += '<div class="seat-aisle"></div>';
            
            // Right side seats
            for (let i = 0; i < rightSeats && seatNumber <= this.totalSeats; i++) {
                html += this.renderSeat(seatNumber, row, i + leftSeats + 1);
                seatNumber++;
            }
            
            html += '</div>';
        }
        
        container.innerHTML = html;
    }
    
    renderSeat(seatNumber, row, position) {
        const seatId = this.getSeatId(row, position);
        const isBooked = this.bookedSeats.includes(seatId);
        const seatClass = isBooked ? 'seat booked' : 'seat available';
        
        return `
            <div class="${seatClass}" 
                 data-seat="${seatId}" 
                 data-number="${seatNumber}"
                 ${isBooked ? 'title="Đã có người đặt"' : ''}>
                ${seatId}
            </div>
        `;
    }
    
    getSeatId(row, position) {
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return letters[position - 1] + row;
    }
    
    attachEventListeners() {
        const container = document.getElementById('seats-container');
        if (!container) return;
        
        container.addEventListener('click', (e) => {
            const seat = e.target.closest('.seat');
            if (!seat || seat.classList.contains('booked')) return;
            
            const seatId = seat.getAttribute('data-seat');
            
            if (seat.classList.contains('selected')) {
                // Deselect
                this.deselectSeat(seatId);
                seat.classList.remove('selected');
            } else {
                // Check max seats limit
                if (this.selectedSeats.length >= this.maxSeats) {
                    showToast(`Bạn chỉ có thể chọn tối đa ${this.maxSeats} ghế`, 'warning');
                    return;
                }
                
                // Select
                this.selectSeat(seatId);
                seat.classList.add('selected');
            }
            
            this.updateSummary();
        });
    }
    
    selectSeat(seatId) {
        if (!this.selectedSeats.includes(seatId)) {
            this.selectedSeats.push(seatId);
        }
    }
    
    deselectSeat(seatId) {
        this.selectedSeats = this.selectedSeats.filter(s => s !== seatId);
    }
    
    updateSummary() {
        const summaryContainer = document.getElementById('booking-summary');
        if (!summaryContainer) return;
        
        const selectedCount = this.selectedSeats.length;
        const totalPrice = selectedCount * this.pricePerSeat;
        
        let html = '<div class="booking-summary-card">';
        
        if (selectedCount === 0) {
            html += `
                <div class="empty-selection">
                    <i class="fas fa-hand-pointer"></i>
                    <p>Vui lòng chọn ghế</p>
                </div>
            `;
        } else {
            html += `
                <div class="selected-seats">
                    <h4>Ghế đã chọn</h4>
                    <div class="seat-tags">
                        ${this.selectedSeats.map(seat => `
                            <span class="seat-tag">${seat}</span>
                        `).join('')}
                    </div>
                </div>
                <div class="price-summary">
                    <div class="price-row">
                        <span>Số ghế:</span>
                        <span>${selectedCount}</span>
                    </div>
                    <div class="price-row">
                        <span>Giá vé:</span>
                        <span>${formatPrice(this.pricePerSeat)}</span>
                    </div>
                    <div class="price-row total">
                        <span>Tổng tiền:</span>
                        <span class="total-price">${formatPrice(totalPrice)}</span>
                    </div>
                </div>
                <button class="btn-continue" onclick="seatMap.continueBooking()">
                    Tiếp tục
                    <i class="fas fa-arrow-right"></i>
                </button>
            `;
        }
        
        html += '</div>';
        summaryContainer.innerHTML = html;
    }
    
    async continueBooking() {
        if (this.selectedSeats.length === 0) {
            showToast('Vui lòng chọn ghế', 'warning');
            return;
        }
        
        // Store selected seats in session/localStorage
        sessionStorage.setItem('selected_seats', JSON.stringify(this.selectedSeats));
        sessionStorage.setItem('trip_id', this.tripId);
        
        // Redirect to booking form
        window.location.href = '/Bus_Booking/user/booking/confirm.php';
    }
    
    getSelectedSeats() {
        return this.selectedSeats;
    }
    
    setPrice(price) {
        this.pricePerSeat = price;
        this.updateSummary();
    }
}

// Initialize seat map
let seatMap = null;

function initSeatMap(tripId, totalSeats, layout, bookedSeats, price) {
    seatMap = new SeatMap(tripId, totalSeats, layout, bookedSeats);
    seatMap.setPrice(price);
}

