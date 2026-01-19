let slideIndex = 1;

// Jalankan setelah halaman siap
document.addEventListener("DOMContentLoaded", () => {
  initSlideshow();
  showSlides(slideIndex); // tampilkan slide pertama
});

// Inisialisasi dot
function initSlideshow() {
  const dotsContainer = document.getElementById("Fasilitas-dots");
  const slides = document.querySelectorAll("#Fasilitas-slideshow .slide");

  slides.forEach((_, i) => {
    const dot = document.createElement("span");
    dot.classList.add("dot");
    dot.addEventListener("click", () => currentSlide(i + 1));
    dotsContainer.appendChild(dot);
  });
}

// Tombol prev/next
function plusSlides(n) {
  showSlides(slideIndex += n);
}

// Klik dot
function currentSlide(n) {
  showSlides(slideIndex = n);
}

// Fungsi utama untuk tampilkan slide
function showSlides(n) {
  const slides = document.querySelectorAll("#Fasilitas-slideshow .slide");
  const dots = document.querySelectorAll("#Fasilitas-dots .dot");

  if (slides.length === 0) return; // safety check

  if (n > slides.length) { slideIndex = 1 }
  if (n < 1) { slideIndex = slides.length }

  slides.forEach(slide => slide.style.display = "none");
  dots.forEach(dot => dot.classList.remove("active"));

  slides[slideIndex - 1].style.display = "block";
  dots[slideIndex - 1].classList.add("active");
}
