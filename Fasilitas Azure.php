<!DOCTYPE html>
<html lang="id">
      <!-- AOS -->
        <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
<head>
  <meta charset="UTF-8">
  <title>Villa Azure</title>
  <link rel="stylesheet" href="Fasilitas.css">
</head>
<body>

  <h1 data-aos="fade-in" data-aos-duration="3000">Villa Azure</h1>
  <div class="villa-card"  data-aos="flip-left" data-aos-duration="1000">
    <div class="slideshow-container" id="Fasilitas-slideshow">
      <div class="slide"><img src="Fasilitas/Azure Fasilitas1(1).png" ></div>
      <div class="slide"><img src="Fasilitas/Azure Fasilitas2(1).png" ></div>
      <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
      <a class="next" onclick="plusSlides(1)">&#10095;</a>
    </div>
    <div class="dots" id="Fasilitas-dots"></div>
    <p><strong>Fasilitas:</strong> 3 Kamar Tidur, Kolam Renang Pribadi, Dapur Modern, WiFi & Smart TV</p>
    <p class="price">IDR 3.200.000 / malam</p>
  </div>

  <script src="Fasilitas.js"></script>
</body>
</html>

<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>