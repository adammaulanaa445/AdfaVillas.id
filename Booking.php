<?php
session_start();
include "Service/database.php";

// Pastikan user login
if (!isset($_SESSION['id_customer'])) {
    header("Location: LoginCustomer.php");
    exit;
}

// Fungsi untuk menentukan apakah suatu tanggal adalah weekend
function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    return ($dayOfWeek >= 5); // 5 = Jumat, 6 = Sabtu, 7 = Minggu
}

// Fungsi untuk mendapatkan label harga
function getPriceLabel($basePrice) {
    $weekendPrice = $basePrice * 1.2; // +20% untuk weekend
    return [
        'weekday' => number_format($basePrice, 0, ',', '.'),
        'weekend' => number_format($weekendPrice, 0, ',', '.')
    ];
}

// Ambil input dari form
$checkin = $_POST['checkin'] ?? date('Y-m-d');
$checkout = $_POST['checkout'] ?? date('Y-m-d', strtotime('+1 day', strtotime($checkin)));
if (strtotime($checkout) <= strtotime($checkin)) {
    $checkout = date('Y-m-d', strtotime('+1 day', strtotime($checkin)));
}

$kota = $_POST['kota'] ?? '';
$lokasi = $_POST['lokasi'] ?? '';
$tamu = $_POST['tamu'] ?? 1;
$id_villa_selected = $_POST['villa'] ?? '';

// Daftar kota (hardcode)
$kota_list = ['Malang', 'Surabaya', 'Batu', 'Pasuruan', 'Probolinggo'];

// Ambil daftar lokasi spesifik berdasarkan kota
$lokasi_list = [];
if (!empty($kota)) {
    $lokasi_result = mysqli_query($conn, "SELECT DISTINCT lokasi_spesifik FROM Villa WHERE kota = '$kota'");
    while ($row = mysqli_fetch_assoc($lokasi_result)) {
        $lokasi_list[] = $row['lokasi_spesifik'];
    }
}

// Query villa HANYA JIKA sudah pilih kota
$villa_data = [];
$booked_dates = []; // Untuk menyimpan tanggal yang sudah dibooking

if (!empty($kota)) {
    // Bangun kondisi query
    $conditions = ["kota = '$kota'", "provinsi = 'Jawa Timur'"];
    
    if (!empty($lokasi)) {
        $conditions[] = "lokasi_spesifik = '$lokasi'";
    }
    
    $where_clause = implode(" AND ", $conditions);
    $query = "SELECT * FROM Villa WHERE $where_clause ORDER BY Nama_villa";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $villa_data[] = $row;
            
            // Ambil tanggal yang sudah dibooking untuk villa ini
            $id_villa = $row['id_villa'];
            $booked_query = "SELECT tanggal_checkin, tanggal_checkout FROM Booking 
                            WHERE id_villa = '$id_villa' 
                            AND status IN ('Menunggu Pembayaran', 'Lunas')";
            $booked_result = mysqli_query($conn, $booked_query);
            
            $villa_booked_dates = [];
            while ($booked = mysqli_fetch_assoc($booked_result)) {
                $start = new DateTime($booked['tanggal_checkin']);
                $end = new DateTime($booked['tanggal_checkout']);
                
                // Tambahkan semua tanggal antara checkin dan checkout
                $current = clone $start;
                while ($current < $end) {
                    $villa_booked_dates[] = $current->format('Y-m-d');
                    $current->modify('+1 day');
                }
            }
            
            $booked_dates[$id_villa] = $villa_booked_dates;
        }
    }
}

// Proses booking
if (isset($_POST['submit'])) {
    $id_customer = $_SESSION['id_customer'];
    $id_villa = $_POST['villa'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $tamu = $_POST['tamu'];

    // Validasi
    if (empty($id_villa)) {
        die("<script>alert('Silakan pilih villa terlebih dahulu!'); window.history.back();</script>");
    }

    // 🔍 CEK BENTROK TANGGAL
    $cek_bentrok = mysqli_query($conn, "
        SELECT id_booking FROM Booking 
        WHERE id_villa = '$id_villa'
        AND status IN ('Menunggu Pembayaran', 'Lunas')
        AND tanggal_checkout > '$checkin'
        AND tanggal_checkin < '$checkout'
    ");

    if (mysqli_num_rows($cek_bentrok) > 0) {
        die("<script>alert('Villa sudah dibooking pada tanggal ini! Silakan pilih tanggal lain.'); window.history.back();</script>");
    }

    // ✅ INSERT BOOKING
    $sql = "INSERT INTO Booking (id_customer, id_villa, tanggal_checkin, tanggal_checkout, jumlah_tamu, status)
            VALUES ('$id_customer', '$id_villa', '$checkin', '$checkout', '$tamu', 'Menunggu Pembayaran')";
    
    if (mysqli_query($conn, $sql)) {
        $id_booking = mysqli_insert_id($conn);
        header("Location: Pilihan Pembayaran.php?id_booking=$id_booking");
        exit;
    } else {
        die("Error: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Booking Villa</title>
  <link rel="stylesheet" href="Booking.css">
  <style>
    .login-berhasil {
        background-color: #d4edda;
        color: #155724;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    
    .booked-date {
        background-color: #ffebee !important;
        color: #c62828 !important;
        border: 1px solid #ef9a9a !important;
    }
    
    .booked-date:hover {
        background-color: #ffcdd2 !important;
        cursor: not-allowed !important;
    }
    
    .warning-message {
        background-color: #fff3cd;
        color: #856404;
        padding: 10px;
        border: 1px solid #ffeaa7;
        border-radius: 4px;
        margin: 10px 0;
        display: none;
    }
    
    .villa-option.booked {
        background-color: #ffebee;
        color: #c62828;
        padding: 5px;
    }
    
    .villa-option.available {
        background-color: #e8f5e9;
        color: #2e7d32;
        padding: 5px;
    }
    
    /* Style baru untuk modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 80%;
      max-width: 500px;
      max-height: 70vh;
      overflow-y: auto;
    }
    
    .close-modal {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close-modal:hover {
      color: black;
    }
    
    .price-info {
      background-color: #e8f5e9;
      padding: 8px 12px;
      border-radius: 4px;
      margin: 5px 0;
      font-size: 14px;
    }
    
    .price-info.weekend {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .show-all-dates {
      color: #007bff;
      cursor: pointer;
      font-size: 12px;
      margin-left: 10px;
      text-decoration: underline;
    }
    
    .show-all-dates:hover {
      color: #0056b3;
    }
    
    .date-list {
      max-height: 200px;
      overflow-y: auto;
      background-color: #f8f9fa;
      padding: 10px;
      border-radius: 4px;
      margin-top: 10px;
    }
    
    .date-list span {
      display: inline-block;
      background-color: #ffebee;
      color: #c62828;
      padding: 3px 8px;
      margin: 2px;
      border-radius: 3px;
      font-size: 12px;
    }
    
    /* Style untuk indikator harga */
    .price-indicator {
      font-size: 12px;
      margin-left: 5px;
      padding: 2px 6px;
      border-radius: 3px;
    }
    
    .price-weekday {
      background-color: #e3f2fd;
      color: #1565c0;
    }
    
    .price-weekend {
      background-color: #fff3cd;
      color: #856404;
    }
    
    /* Style untuk daftar villa */
    .villa-list-item {
      margin-bottom: 15px;
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }
    
    .total-price-display {
      background-color: #e3f2fd;
      padding: 10px;
      border-radius: 5px;
      margin-top: 10px;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <?php if (isset($_SESSION['login_berhasil'])): ?>
    <div class="login-berhasil">✅ Login berhasil! Selamat datang di sistem booking villa.</div>
    <?php unset($_SESSION['login_berhasil']); ?>
  <?php endif; ?>

  <form action="Booking.php" method="POST" id="bookingForm">
    <h2>Form Booking Villa</h2>

    <!-- PILIH KOTA (HANYA INI YANG AUTO-SUBMIT) -->
    <div>
      <label for="kota">Pilih Kota (Jawa Timur)</label>
      <select name="kota" id="kota" onchange="document.getElementById('bookingForm').submit()" required>
        <option value="">-- Pilih Kota --</option>
        <?php foreach ($kota_list as $k): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $kota == $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- PILIH LOKASI SPESIFIK -->
    <?php if (!empty($kota)): ?>
      <div style="margin-top: 15px;">
        <label for="lokasi">Lokasi Spesifik</label>
        <select name="lokasi" id="lokasi" onchange="document.getElementById('bookingForm').submit()">
          <option value="">-- Semua Lokasi --</option>
          <?php foreach ($lokasi_list as $l): ?>
            <option value="<?= htmlspecialchars($l) ?>" <?= $lokasi == $l ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <!-- JIKA SUDAH PILIH KOTA, TAMPILKAN FORM LAINNYA -->
    <?php if (!empty($kota)): ?>
      <div style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;">
        <h3>Informasi Booking</h3>
        
        <!-- TANGGAL CHECK-IN & CHECK-OUT -->
        <div>
          <label for="checkin">Tanggal Check-In</label>
          <input type="date" id="checkin" name="checkin" required 
                 value="<?= $checkin ?>" 
                 min="<?= date('Y-m-d') ?>"
                 onchange="updateMinCheckout(); checkBookingAvailability(); calculateTotalPrice();">
        </div>
        
        <div style="margin-top: 10px;">
          <label for="checkout">Tanggal Check-Out</label>
          <input type="date" id="checkout" name="checkout" required 
                 value="<?= $checkout ?>"
                 onchange="checkBookingAvailability(); calculateTotalPrice();">
        </div>
        
        <div style="margin-top: 10px;">
          <label for="tamu">Jumlah Tamu</label>
          <input type="number" id="tamu" name="tamu" min="1" required value="<?= $tamu ?>">
        </div>
        
        <!-- PERKIRAAN HARGA TOTAL -->
        <div id="totalPriceDisplay" class="total-price-display" style="display: none;">
          <strong>Perkiraan Total:</strong> <span id="totalPriceText">Rp 0</span><br>
          <small id="priceBreakdown"></small>
        </div>
        
        <!-- PESAN PERINGATAN -->
        <div id="warningMessage" class="warning-message"></div>
        
        <!-- PILIH VILLA DARI KOTA YANG DIPILIH -->
        <div style="margin-top: 15px;">
          <label for="villa">Pilih Villa 
            <?php if (!empty($lokasi)): ?>
              di <?= htmlspecialchars($lokasi) ?>
            <?php else: ?>
              di <?= htmlspecialchars($kota) ?>
            <?php endif; ?>
          </label>
          <select id="villa" name="villa" required style="width: 100%; padding: 8px;" onchange="checkVillaAvailability(); calculateTotalPrice();">
            <option value="">-- Pilih Villa --</option>
            <?php if (!empty($villa_data)): ?>
              <?php foreach ($villa_data as $row): 
                $is_booked = false;
                $checkin_date = $checkin;
                $checkout_date = $checkout;
                
                // Cek apakah villa sudah dibooking pada tanggal yang dipilih
                if (!empty($checkin_date) && !empty($checkout_date) && isset($booked_dates[$row['id_villa']])) {
                  $start = new DateTime($checkin_date);
                  $end = new DateTime($checkout_date);
                  $current = clone $start;
                  
                  while ($current < $end) {
                    $current_date = $current->format('Y-m-d');
                    if (in_array($current_date, $booked_dates[$row['id_villa']])) {
                      $is_booked = true;
                      break;
                    }
                    $current->modify('+1 day');
                  }
                }
                
                $priceLabel = getPriceLabel($row['harga']);
              ?>
                <option value="<?= $row['id_villa'] ?>" 
                        data-base-price="<?= $row['harga'] ?>"
                        data-booked-dates='<?= json_encode($booked_dates[$row['id_villa']] ?? []) ?>'
                        class="<?= $is_booked ? 'villa-option booked' : 'villa-option available' ?>"
                        <?= ($is_booked && $id_villa_selected == $row['id_villa']) ? 'disabled' : '' ?>
                        <?= $id_villa_selected == $row['id_villa'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($row['Nama_villa']) ?> 
                  - Rp <?= number_format($row['harga'], 0, ',', '.') ?>/malam (Weekday)
                  <span class="price-indicator price-weekend">Weekend: Rp <?= $priceLabel['weekend'] ?></span>
                  (<?= $row['lokasi_spesifik'] ?>)
                  <?= $is_booked ? ' ⚠ TERSIBUK' : '' ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="" disabled>
                Tidak ada villa tersedia 
                <?php if (!empty($lokasi)): ?>
                  di <?= htmlspecialchars($lokasi) ?>
                <?php else: ?>
                  di <?= htmlspecialchars($kota) ?>
                <?php endif; ?>
              </option>
            <?php endif; ?>
          </select>
          
          <div style="margin-top: 10px; font-size: 12px; color: #666;">
            <span style="color: #000000ff;">✓ Tidak ada keterangan "TERSIBUK"</span> = Tersedia | 
            <span style="color: #949494ff;">✗ Pilihan Villa Berwarna abu abu" #1565c0</span> = Sudah dibooking pada tanggal yang dipilih
          </div>
        </div>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
          <span style="color: #2e7d32;">💖 Keuntungan Anda: Harga konsisten! Liburan kapan saja tanpa khawatir biaya tambahan</span>
        </div>
        
        <button type="submit" name="submit" id="submitBtn" 
                style="margin-top: 20px; padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; width: 100%;">
          Booking Villa
        </button>
      </div>
      
      <!-- INFO VILLA YANG TERSEDIA -->
      <div style="margin-top: 15px; padding: 10px; background: #e9f7fe; border: 1px solid #b3e0ff;">
        <h4>Villa tersedia 
          <?php if (!empty($lokasi)): ?>
            di <?= htmlspecialchars($lokasi) ?>
          <?php else: ?>
            di <?= htmlspecialchars($kota) ?>
          <?php endif; ?>:
        </h4>
        <?php if (!empty($villa_data)): ?>
          <ul style="list-style-type: none; padding: 0;">
            <?php foreach ($villa_data as $row): 
              $priceLabel = getPriceLabel($row['harga']);
              $hasBookedDates = !empty($booked_dates[$row['id_villa']]);
            ?>
              <li class="villa-list-item">
                <strong><?= htmlspecialchars($row['Nama_villa']) ?></strong> - 
                <?= $row['lokasi_spesifik'] ?>
                
                <!-- INFO HARGA -->
                <div class="price-info">
                  <strong>Harga per malam:</strong><br>
                  • <span class="price-indicator price-weekday">Weekday (Senin-Kamis)</span>: <strong>Rp <?= $priceLabel['weekday'] ?></strong><br>
                  • <span class="price-indicator price-weekend">Weekend (Jumat-Minggu)</span>: <strong>Rp <?= $priceLabel['weekend'] ?></strong>
                  <small style="color: #856404;">(+20% dari harga weekday)</small>
                </div>
                
                <?php if ($hasBookedDates): ?>
                  <div style="margin-top: 8px;">
                    <small style="color: #c62828;">
                      ⚠ Sudah dibooking pada: 
                      <?php 
                      $displayDates = array_slice($booked_dates[$row['id_villa']], 0, 3);
                      $totalDates = count($booked_dates[$row['id_villa']]);
                      
                      foreach ($displayDates as $index => $date) {
                          $dayName = date('D', strtotime($date));
                          $formattedDate = date('d/m/Y', strtotime($date));
                          echo "$dayName $formattedDate";
                          if ($index < count($displayDates) - 1) echo ', ';
                      }
                      
                      if ($totalDates > 3): 
                      ?>
                        <span class="show-all-dates" 
                              onclick="showAllDates('<?= $row['Nama_villa'] ?>', <?= htmlspecialchars(json_encode($booked_dates[$row['id_villa']])) ?>)">
                          dan <?= $totalDates - 3 ?> tanggal lainnya...
                        </span>
                      <?php endif; ?>
                    </small>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>Tidak ada villa tersedia 
            <?php if (!empty($lokasi)): ?>
              di <?= htmlspecialchars($lokasi) ?>
            <?php else: ?>
              di <?= htmlspecialchars($kota) ?>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </form>

  <!-- MODAL UNTUK MENAMPILKAN SEMUA TANGGAL -->
  <div id="datesModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeModal()">&times;</span>
      <h3 id="modalTitle">Tanggal Terbooking</h3>
      <div id="modalDates" class="date-list">
        <!-- Tanggal akan dimuat di sini via JavaScript -->
      </div>
    </div>
  </div>

  <script>
    // Data tanggal yang sudah dibooking (dari PHP)
    const bookedDatesByVilla = <?= json_encode($booked_dates) ?>;
    
    // Fungsi update tanggal checkout
    function updateMinCheckout() {
      const checkinInput = document.getElementById('checkin');
      const checkoutInput = document.getElementById('checkout');
      
      if (checkinInput.value) {
        const checkinDate = new Date(checkinInput.value);
        const nextDay = new Date(checkinDate);
        nextDay.setDate(nextDay.getDate() + 1);
        
        const nextDayStr = nextDay.toISOString().split('T')[0];
        checkoutInput.min = nextDayStr;
        
        // Jika checkout lebih awal dari checkin, update checkout
        if (checkoutInput.value && checkoutInput.value <= checkinInput.value) {
          checkoutInput.value = nextDayStr;
        }
      }
      
      // Cek ketersediaan setelah update tanggal
      checkBookingAvailability();
      calculateTotalPrice();
    }
    
    // Cek ketersediaan booking berdasarkan tanggal
    function checkBookingAvailability() {
      const checkin = document.getElementById('checkin').value;
      const checkout = document.getElementById('checkout').value;
      const villaSelect = document.getElementById('villa');
      const warningMessage = document.getElementById('warningMessage');
      
      if (!checkin || !checkout) return;
      
      // Reset semua opsi
      const options = villaSelect.options;
      let anyAvailable = false;
      
      for (let i = 0; i < options.length; i++) {
        const option = options[i];
        const villaId = option.value;
        
        if (villaId && bookedDatesByVilla[villaId]) {
          // Cek apakah tanggal yang dipilih bentrok dengan tanggal yang sudah dibooking
          const isBooked = checkDateOverlap(checkin, checkout, bookedDatesByVilla[villaId]);
          
          if (isBooked) {
            option.classList.add('villa-option', 'booked');
            option.classList.remove('villa-option', 'available');
            option.disabled = true;
            
            // Update teks opsi
            if (!option.textContent.includes('TERSIBUK')) {
              option.textContent = option.textContent.replace(' ⚠ TERSIBUK', '') + ' ⚠ TERSIBUK';
            }
          } else {
            option.classList.add('villa-option', 'available');
            option.classList.remove('villa-option', 'booked');
            option.disabled = false;
            anyAvailable = true;
            
            // Hapus teks TERSIBUK
            option.textContent = option.textContent.replace(' ⚠ TERSIBUK', '');
          }
        }
      }
      
      // Tampilkan pesan peringatan
      if (!anyAvailable && options.length > 1) {
        warningMessage.innerHTML = '⚠ Semua villa sudah dibooking pada tanggal yang dipilih. Silakan pilih tanggal lain.';
        warningMessage.style.display = 'block';
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').style.backgroundColor = '#ccc';
      } else {
        warningMessage.style.display = 'none';
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').style.backgroundColor = '#007bff';
      }
    }
    
    // Cek apakah tanggal bentrok
    function checkDateOverlap(checkin, checkout, bookedDates) {
      const start = new Date(checkin);
      const end = new Date(checkout);
      const current = new Date(start);
      
      while (current < end) {
        const currentStr = current.toISOString().split('T')[0];
        if (bookedDates.includes(currentStr)) {
          return true;
        }
        current.setDate(current.getDate() + 1);
      }
      return false;
    }
    
    // Cek ketersediaan villa yang dipilih
    function checkVillaAvailability() {
      const villaSelect = document.getElementById('villa');
      const selectedOption = villaSelect.options[villaSelect.selectedIndex];
      const warningMessage = document.getElementById('warningMessage');
      
      if (selectedOption.disabled) {
        warningMessage.innerHTML = '⚠ Villa ini sudah dibooking pada tanggal yang dipilih. Silakan pilih villa lain atau ubah tanggal.';
        warningMessage.style.display = 'block';
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').style.backgroundColor = '#ccc';
      } else {
        warningMessage.style.display = 'none';
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').style.backgroundColor = '#007bff';
      }
      
      calculateTotalPrice();
    }
    
    // Fungsi untuk menghitung total harga
    function calculateTotalPrice() {
      const checkin = document.getElementById('checkin')?.value;
      const checkout = document.getElementById('checkout')?.value;
      const villaSelect = document.getElementById('villa');
      const selectedOption = villaSelect?.options[villaSelect?.selectedIndex];
      const totalPriceDisplay = document.getElementById('totalPriceDisplay');
      const totalPriceText = document.getElementById('totalPriceText');
      const priceBreakdown = document.getElementById('priceBreakdown');
      
      if (!checkin || !checkout || !selectedOption?.value || !selectedOption.dataset.basePrice) {
        totalPriceDisplay.style.display = 'none';
        return;
      }
      
      const basePrice = parseFloat(selectedOption.dataset.basePrice);
      const weekendMultiplier = 1.2;
      const weekendPrice = basePrice * weekendMultiplier;
      
      // Hitung jumlah malam
      const start = new Date(checkin);
      const end = new Date(checkout);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      // Hitung jumlah weekend dan weekday
      let weekendCount = 0;
      let weekdayCount = 0;
      let current = new Date(start);
      
      while (current < end) {
        const dayOfWeek = current.getDay();
        if (dayOfWeek === 5 || dayOfWeek === 6 || dayOfWeek === 0) { // 5=Jumat, 6=Sabtu, 0=Minggu
          weekendCount++;
        } else {
          weekdayCount++;
        }
        current.setDate(current.getDate() + 1);
      }
      
      // Hitung total harga
      const totalPrice = (weekdayCount * basePrice) + (weekendCount * weekendPrice);
      
      // Format angka menjadi Rupiah
      const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(number);
      };
      
      // Tampilkan hasil
      totalPriceText.textContent = formatRupiah(totalPrice);
      priceBreakdown.innerHTML = `
        ${diffDays} malam (${weekdayCount} weekday × Rp ${basePrice.toLocaleString('id-ID')} + 
        ${weekendCount} weekend × Rp ${weekendPrice.toLocaleString('id-ID')})
      `;
      
      totalPriceDisplay.style.display = 'block';
    }
    
    // Fungsi untuk menampilkan semua tanggal terbooking
    function showAllDates(villaName, datesArray) {
      const modal = document.getElementById('datesModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalDates = document.getElementById('modalDates');
      
      // Urutkan tanggal
      datesArray.sort();
      
      // Buat HTML untuk menampilkan tanggal
      let html = `<h4>${villaName}</h4>`;
      html += `<p><strong>Total: ${datesArray.length} tanggal terbooking</strong></p>`;
      html += `<div class="date-list">`;
      
      datesArray.forEach(date => {
        const dateObj = new Date(date);
        const formattedDate = dateObj.toLocaleDateString('id-ID', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        const dayName = dateObj.toLocaleDateString('id-ID', { weekday: 'short' });
        
        html += `<span title="${formattedDate}">${dayName} ${dateObj.getDate()}/${dateObj.getMonth()+1}</span> `;
      });
      
      html += `</div>`;
      
      modalTitle.innerHTML = `Semua Tanggal Terbooking - ${villaName}`;
      modalDates.innerHTML = html;
      modal.style.display = 'block';
    }
    
    // Fungsi untuk menutup modal
    function closeModal() {
      document.getElementById('datesModal').style.display = 'none';
    }
    
    // Tutup modal jika klik di luar konten
    window.onclick = function(event) {
      const modal = document.getElementById('datesModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
    
    // Set min date saat halaman load
    window.onload = function() {
      const today = new Date().toISOString().split('T')[0];
      if (document.getElementById('checkin')) {
        document.getElementById('checkin').min = today;
        
        // Set min checkout ke besok
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('checkout').min = tomorrow.toISOString().split('T')[0];
        
        // Update min checkout berdasarkan checkin yang sudah ada
        updateMinCheckout();
        
        // Cek ketersediaan awal
        checkBookingAvailability();
        
        // Hitung harga total awal
        calculateTotalPrice();
      }
    };
    
    // TAMBAHAN SEDERHANA: KONFIRMASI SEBELUM SUBMIT (SUDAH DIPERBAIKI)
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
      // JANGAN pakai e.preventDefault() di awal!
      
      const villaSelect = document.getElementById('villa');
      if (!villaSelect.value) {
        e.preventDefault(); // Hanya prevent jika villa belum dipilih
        alert('Silakan pilih villa terlebih dahulu!');
        villaSelect.focus();
        return;
      }
      
      const selectedOption = villaSelect.options[villaSelect.selectedIndex];
      const villaName = selectedOption.text.split('-')[0].trim();
      
      // Tampilkan konfirmasi
      const isConfirmed = confirm(`Apakah Anda yakin memilih villa "${villaName}"?\n\nKlik "OK" untuk melanjutkan ke pembayaran.`);
      
      if (!isConfirmed) {
        e.preventDefault(); // Hanya stop jika user klik Cancel
      }
      // Jika OK, biarkan form submit normal ke PHP
    });
    
  </script>

</body>
</html>