// villa.js
    function searchVilla() {
      const input = document.getElementById('searchInput').value.toLowerCase();
      const villas = document.querySelectorAll('#villaList .villa-card');
      
      villas.forEach(villa => {
        const name = villa.getAttribute('data-name').toLowerCase();
        villa.style.display = name.includes(input) ? '' : 'none';
      });
    }