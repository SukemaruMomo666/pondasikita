<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$chat_session_id_from_php = session_id(); 
?>

<link rel="stylesheet" href="./partials/chat/css/style.css">

<div class="chat-button" id="chatButton">
  <svg fill="#000000" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 458 458" xml:space="preserve">
    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    <g id="SVGRepo_iconCarrier">
      <g>
        <g>
          <path d="M428,41.534H30c-16.569,0-30,13.431-30,30v252c0,16.568,13.432,30,30,30h132.1l43.942,52.243 c5.7,6.777,14.103,10.69,22.959,10.69c8.856,0,17.258-3.912,22.959-10.69l43.942-52.243H428c16.568,0,30-13.432,30-30v-252 C458,54.965,444.568,41.534,428,41.534z M323.916,281.534H82.854c-8.284,0-15-6.716-15-15s6.716-15,15-15h241.062 c8.284,0,15,6.716,15,15S332.2,281.534,323.916,281.534z M67.854,198.755c0-8.284,6.716-15,15-15h185.103c8.284,0,15,6.716,15,15 s-6.716,15-15,15H82.854C74.57,207.039,67.854,207.039,67.854,198.755z M375.146,145.974H82.854c-8.284,0-15-6.716-15-15 s6.716-15,15-15h292.291c8.284,0,15,6.716,15,15C390.146,139.258,383.43,145.974,375.146,145.974z"></path>
        </g>
      </g>
    </g>
  </svg>
  <span class="notification-badge" id="chatNotificationBadge">0</span> </div>
</div>

<div class="chat-panel" id="chatPanel">
  <div class="chat-header" id="chatHeader">
    <div class="chat-header-left">
      <img src="./assets/images/profile-picture/image2.png" alt="SiPonda" />
      <div>
        <h3>SiPonda</h3>
        <p class="admin-status" id="adminStatus">Online</p>
      </div>
    </div>
    <div class="chat-actions">
      <button class="chat-action-btn" id="fullscreenBtn">
        <span>
          <svg id="iconFullscreenBtn" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="white">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
              <title>full_screen [#904]</title>
              <desc>Created with Sketch.</desc>
              <defs> </defs>
              <g id="Page-1" stroke="none" stroke-width="1" fill="white" fill-rule="evenodd">
                <g id="Dribbble-Light-Preview" transform="translate(-300.000000, -4199.000000)" fill="white">
                  <g id="icons" transform="translate(56.000000, 160.000000)">
                    <path d="M262.4445,4039 L256.0005,4039 L256.0005,4041 L262.0005,4041 L262.0005,4047 L264.0005,4047 L264.0005,4039.955 L264.0005,4039 L262.4445,4039 Z M262.0005,4057 L256.0005,4057 L256.0005,4059 L262.4445,4059 L264.0005,4059 L264.0005,4055.955 L264.0005,4051 L262.0005,4051 L262.0005,4057 Z M246.0005,4051 L244.0005,4051 L244.0005,4055.955 L244.0005,4059 L246.4445,4059 L252.0005,4059 L252.0005,4057 L246.0005,4057 L246.0005,4051 Z M246.0005,4047 L244.0005,4047 L244.0005,4039.955 L244.0005,4039 L246.4445,4039 L252.0005,4039 L252.0005,4041 L246.0005,4041 L246.0005,4047 Z" id="full_screen-[#904]"> </path>
                </g>
              </g>
            </g>
          </svg>
        </span>
      </button>
      <button class="chat-action-btn" id="closeBtn"><span>
          <svg viewBox="0 0 16 16" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/svg" version="1.1" id="svg8" fill="#000000">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
              <metadata id="metadata5">
                <rdf:rdf>
                  <cc:work>
                    <dc:format>image/svg+xml</dc:format>
                    <dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage"></dc:type>
                    <dc:title></dc:title>
                    <dc:date>2021</dc:date>
                    <dc:creator>
                      <cc:agent>
                        <dc:title>Timothée Giet</dc:title>
                      </cc:agent>
                    </dc:creator>
                    <cc:license rdf:resource="http://creativecommons.org/licenses/by-sa/4.0/"></cc:license>
                  </cc:work>
                  <cc:license rdf:about="http://creativecommons.org/licenses/by-sa/4.0/">
                    <cc:permits rdf:resource="http://creativecommons.org/ns#Reproduction"></cc:permits>
                    <cc:permits rdf:resource="http://creativecommons.org/ns#Distribution"></cc:permits>
                    <cc:requires rdf:resource="http://creativecommons.org/ns#Notice"></cc:requires>
                    <cc:requires rdf:resource="http://creativecommons.org/ns#Attribution"></cc:requires>
                    <cc:permits rdf:resource="http://creativecommons.org/ns#DerivativeWorks"></cc:permits>
                    <cc:requires rdf:resource="http://creativecommons.org/ns#ShareAlike"></cc:requires>
                  </cc:license>
                </rdf:rdf>
              </metadata>
              <rect transform="rotate(45)" ry="0" y="-1" x="4.3137083" height="2" width="14" id="rect1006" style="opacity:1;vector-effect:none;fill:#ffffff;fill-opacity:1;stroke:none;stroke-width:4;stroke-linecap:square;stroke-linejoin:round;stroke-miterlimit:4;stroke-dasharray:none;stroke-dashoffset:3.20000005;stroke-opacity:1"></rect>
              <rect transform="rotate(-45)" ry="0" y="10.313708" x="-7" height="2" width="14" id="rect1006-5" style="opacity:1;vector-effect:none;fill:#ffffff;fill-opacity:1;stroke:none;stroke-width:4;stroke-linecap:square;stroke-linejoin:round;stroke-miterlimit:4;stroke-dasharray:none;stroke-dashoffset:3.20000005;stroke-opacity:1"></rect>
            </g>
          </svg>
        </span></button>
    </div>
  </div>
  <div class="chat-body" id="chatBody">
    <div class="message bot-message">Halo! Selamat datang di Pondasikita.com!</div>
    <div class="message bot-message">
      <strong>Saya SiPonda, asisten AI cerdas Anda. Ada yang bisa saya bantu?</strong>
    </div>
    <div class="message bot-message">
      <strong>Anda bisa bertanya tentang:</strong>
      <div class="promo-card" data-target="about">
        <h4>Tentang Pondasikita.com</h4>
        <p>Pelajari lebih lanjut tentang platform kami</p>
      </div>
      <div class="promo-card" data-target="products">
        <h4>Cari Produk Bahan Bangunan</h4>
        <p>Temukan produk sesuai kebutuhan Anda</p>
      </div>
      <div class="promo-card" data-target="payment">
        <h4>Metode Pembayaran</h4>
        <p>Informasi cara bertransaksi</p>
      </div>
      <div class="promo-card" data-target="delivery">
        <h4>Info Pengiriman</h4>
        <p>Cara produk Anda sampai tujuan</p>
      </div>
    </div>
    <div class="message bot-message">
      <strong>Atau ketikkan pertanyaan Anda.</strong>
    </div>
  </div>
  <div class="chat-input">
    <input
      type="text"
      placeholder="Punya pertanyaan, atau saran? Ketik disini!"
      id="chatInput" />
    <button id="sendButton">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round">
        <line x1="22" y1="2" x2="11" y2="13"></line>
        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
      </svg>
    </button>
  </div>
</div>

<div class="full-page-chat" id="fullPageChat">
  <div class="full-page-header">
    <button class="back-button" id="backButton">← Kembali</button>
    <h3>SiPonda - Asisten AI</h3>
    <div style="width: 40px"></div>
  </div>
  <div class="chat-body" id="fullPageChatBody"></div>
  <div class="chat-input">
    <input
      type="text"
      placeholder="Ketik pesan Anda..."
      id="fullPageInput" />
    <button id="fullPageSendButton">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round">
        <line x1="22" y1="2" x2="11" y2="13"></line>
        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
      </svg>
    </button>
  </div>
</div>
<script>
  const isCustomerLoggedIn = <?php echo json_encode(isset($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0); ?>;
  let chatSessionId = <?php echo json_encode($chat_session_id_from_php); ?>;
</script>

<script src="./partials/chat/js/chat-ui.js"></script>
<script src="./partials/chat/js/koneksi-api-chat.js"></script>
<script src="./partials/chat/js/chatbot.js"></script>