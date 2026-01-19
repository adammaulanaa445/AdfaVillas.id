 <header class="site-header">
  <!-- AOS -->
       <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <div class="container header-inner">
      <a class="brand" href="#" data-aos="fade-in" data-aos-duration="1000">AdfaVilla's</a>
      <nav class="nav">
        <a href="Registrasi.php" data-aos="fade-in" data-aos-duration="1500">Booking </a>
        <a href="Tentang Kami.php" data-aos="fade-in" data-aos-duration="2000">Tentang Kami</a>
      </nav>
    </div>
  </header>

   <section class="hero" data-aos="fade-in" data-aos-duration="1000">
    <div class="hero-overlay"></div>
    <div class="container hero-inner">
      <div class="hero-content" data-aos="fade-right" data-aos-duration="1000">
        <h1>Villa Mewah untuk Liburan Impian</h1>
        <p class="lead" data-aos="fade-in" data-aos-delay="600" data-aos-duration="1000">Temukan villa terbaik dengan fasilitas premium, kolam renang pribadi, dan pemandangan yang indah.</p>
        <div class="hero-actions"  data-aos="fade-in" data-aos-delay="1000" data-aos-duration="1000">
          <a class="btn-primary" href="#villas">Lihat Villa</a>
        </div>
      </div>
    </div>
  </section>

  <main class="container">
    <!-- Pencarian -->
    <section class="search-section" data-aos="fade-in" data-aos-duration="1000">
      <input type="text" id="searchInput" placeholder="Cari nama villa..." onkeyup="searchVilla()">
    </section>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>