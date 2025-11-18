async function requestNotificationPermission() {
  if (!("Notification" in window)) {
    console.warn("Browser ini tidak mendukung notifikasi desktop.");
    return false;
  }

  if (Notification.permission === "granted") {
    console.log("Izin notifikasi sudah diberikan.");
    return true;
  }

  if (Notification.permission === "denied") {
    console.warn("Izin notifikasi ditolak oleh pengguna.");
    return false;
  }

  try {
    const permission = await Notification.requestPermission();
    if (permission === "granted") {
      console.log("Izin notifikasi diberikan.");
      return true;
    } else {
      console.warn("Izin notifikasi tidak diberikan.");
      return false;
    }
  } catch (error) {
    console.error("Kesalahan saat meminta izin notifikasi:", error);
    return false;
  }
}

function showChatNotification(title, body) {
  if (Notification.permission === "granted") {
    if (!chatPanel.classList.contains("active")) {
      new Notification(title, {
        body: body,
        icon: "./assets/images/profile-picture/the-winner.jpeg",
      });
    }
  } else {
    console.warn("Tidak dapat menampilkan notifikasi: Izin tidak diberikan.");
  }
}

const displayedMessageIds = new Set();

setInterval(async () => {
  if (chatSessionId) {
    try {
      const isChatPanelCurrentlyActive = chatPanel.classList.contains("active");
      const response = await fetch(
        `./api/chat.php?action=get_new_messages_customer&session_id=${chatSessionId}&is_chat_active=${isChatPanelCurrentlyActive}`
      );
      const data = await response.json();

      if (data.success && data.new_messages.length > 0) {
        data.new_messages.forEach((msg) => {
          if (!displayedMessageIds.has(msg.id)) {
            appendMessage(msg.sender_type, msg.message_text, chatBody.id);
            appendMessage(msg.sender_type, msg.message_text, fullPageChatBody.id);

            displayedMessageIds.add(msg.id);
            if (msg.sender_type === 'bot') {
              showChatNotification("Pesan Baru dari SiPonda", msg.message_text);
            }
          }
        });

        attachPromoCardListeners();
        console.log("Pesan baru diterima dan ditambahkan:", data.new_messages.length);
      }
    } catch (error) {
      console.error("Kesalahan polling untuk pesan baru:", error);
    }
  }
  await updateUnreadMessageCount();
}, 1000);

document.addEventListener("DOMContentLoaded", async () => {
  await getOrCreateChatSession();
  await fetchProductsChat();
  await loadChatHistory();
  adminStatusElement.textContent = "Online";
  adminStatusElement.classList.remove("offline");
  adminStatusElement.classList.add("online");
  await requestNotificationPermission();
  await updateUnreadMessageCount();
});

async function fetchProductsChat() {
  try {
    const response = await fetch("./api/chat.php?action=get_products");
    const data = await response.json();

    if (data.success) {
      productsChat = data.products;
      console.log("Produk berhasil diambil:", productsChat);
    } else {
      console.error("Gagal mengambil produk:", data.message);
      const errorMessage = "Gagal memuat daftar produk. Silakan coba lagi nanti.";
      appendMessage("bot", errorMessage, chatBody.id);
      appendMessage("bot", errorMessage, fullPageChatBody.id);
    }
  } catch (error) {
    console.error("Kesalahan jaringan saat mengambil produk:", error);
    const errorMessage = "Terjadi kesalahan jaringan saat memuat daftar produk.";
    appendMessage("bot", errorMessage, chatBody.id);
    appendMessage("bot", errorMessage, fullPageChatBody.id);
  }
}

async function sendChatMessageToBackend(message, senderType) {
  try {
    const response = await fetch("./api/chat.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        action: "send_message",
        message: message,
        session_id: chatSessionId,
        sender_type: senderType,
      }),
    });
    const data = await response.json();
    if (!data.success) {
      console.error("Gagal mengirim pesan ke backend:", data.message);
    }
    return data;
  } catch (error) {
    console.error("Kesalahan saat mengirim pesan ke backend:", error);
    return {
      success: false,
      message: "Kesalahan jaringan"
    };
  }
}

async function sendTypingStatus(isTyping) {
  if (!chatSessionId) return;

  if (isTyping !== isCustomerTyping) {
    isCustomerTyping = isTyping;
    try {
      await fetch("./api/chat.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "update_typing_status",
          session_id: chatSessionId,
          is_typing: isTyping,
          who: "customer",
        }),
      });
      console.log("Status mengetik customer dikirim:", isTyping);
    } catch (error) {
      console.error("Kesalahan saat mengirim status mengetik customer:", error);
    }
  }
}

async function getOrCreateChatSession() {
  if (chatSessionId && chatSessionId !== '') {
    try {
      const response = await fetch("./api/chat.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "check_session",
          session_id: chatSessionId,
        }),
      });
      const data = await response.json();
      if (!data.success) {
        chatSessionId = null;
        console.warn("Sesi chat yang ada tidak valid atau ditutup, mencoba membuat yang baru.");
      } else {
        console.log("Sesi chat dari PHP valid dan aktif.");
        return;
      }
    } catch (error) {
      console.error("Kesalahan memeriksa sesi yang ada:", error);
      chatSessionId = null;
    }
  }

  if (!chatSessionId) {
    try {
      const response = await fetch("./api/chat.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "get_or_create_session",
        }),
      });
      const data = await response.json();
      if (data.success) {
        chatSessionId = data.session_id;
        console.log("Sesi chat baru dibuat:", chatSessionId);
      } else {
        console.error("Gagal mendapatkan atau membuat sesi chat:", data.message);
      }
    } catch (error) {
      console.error("Kesalahan jaringan saat membuat sesi chat:", error);
    }
  }
}

async function loadChatHistory() {
  if (!chatSessionId) return;

  try {
    const response = await fetch(
      `./api/chat.php?action=get_history&session_id=${chatSessionId}`
    );
    const data = await response.json();

    if (data.success) {
      chatBody.innerHTML = "";
      fullPageChatBody.innerHTML = "";
      displayedMessageIds.clear();

      const initialBotMessagesHTML = `
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
`;

      chatBody.innerHTML = initialBotMessagesHTML;
      fullPageChatBody.innerHTML = initialBotMessagesHTML;

      data.messages.forEach((msg) => {
        const staticMessagesToExclude = [
          "Halo! Selamat datang di Pondasikita.com!",
          "Saya SiPonda, asisten AI cerdas Anda. Ada yang bisa saya bantu?",
          "Anda bisa bertanya tentang:",
          "Tentang Pondasikita.com",
          "Pelajari lebih lanjut tentang platform kami",
          "Cari Produk Bahan Bangunan",
          "Temukan produk sesuai kebutuhan Anda",
          "Metode Pembayaran",
          "Informasi cara bertransaksi",
          "Info Pengiriman",
          "Cara produk Anda sampai tujuan",
          "Atau ketikkan pertanyaan Anda.",
        ].map(s => s.trim());

        const isExactStaticMessage = staticMessagesToExclude.includes(msg.message_text.trim());
        const isPromoCardBotResponse = msg.sender_type === "bot" && msg.message_text.includes("promo-card");
        const isNegotiationFlowMessage =
          msg.message_text.includes("Terima kasih atas penawaran Anda!") ||
          msg.message_text.includes("Gagal memproses penawaran Anda:") ||
          msg.message_text.includes("Silakan coba tanyakan kembali, atau pilih opsi lain:") ||
          msg.message_text.includes("Berikut beberapa produk terbaru/populer di Pondasikita.com") ||
          (msg.message_text.includes("Anda memilih ") && msg.message_text.includes("Untuk detail lebih lanjut atau untuk melakukan pembelian/negosiasi, silakan"));


        if (
          (!isExactStaticMessage && !isPromoCardBotResponse) ||
          isNegotiationFlowMessage ||
          msg.sender_type === "customer" ||
          msg.sender_type === "guest" ||
          msg.sender_type === "user"
        ) {
          if (msg.id) {
            displayedMessageIds.add(msg.id);
          }
          appendMessage(msg.sender_type, msg.message_text, chatBody.id);
          appendMessage(msg.sender_type, msg.message_text, fullPageChatBody.id);
        }
      });

      attachPromoCardListeners();
    } else {
      console.error("Gagal memuat riwayat chat:", data.message);
    }
  } catch (error) {
    console.error("Kesalahan jaringan memuat riwayat chat:", error);
  }
}

async function updateAdminStatus() {
  adminStatusElement.textContent = "Online";
  adminStatusElement.classList.remove("offline");
  adminStatusElement.classList.add("online");
}

async function getOpponentTypingStatus() {
}