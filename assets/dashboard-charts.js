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
  data: {
    labels: ['Ocean View', 'Garden View', 'Family Room'],
    datasets: [{
      data: [10, 6, 4],
      backgroundColor: ['#0d6efd', '#198754', '#ffc107']
    }]
  }
});
