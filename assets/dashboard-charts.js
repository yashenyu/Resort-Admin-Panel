// BOOKINGS PER MONTH
const ctx1 = document.getElementById('bookingsChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: bookingsData
});

// ROOM TYPES
const ctx2 = document.getElementById('roomTypeChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: roomTypeData
});
