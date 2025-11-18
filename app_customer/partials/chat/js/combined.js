// partials/chat/js/chat-ui.js
// (Ini adalah bagian dari chat-ui.js yang digabungkan untuk kemudahan)

const chatButton = document.getElementById("chatButton");
const chatPanel = document.getElementById("chatPanel");
const closeBtn = document.getElementById("closeBtn");
const fullscreenBtn = document.getElementById("fullscreenBtn");
const fullPageChat = document.getElementById("fullPageChat");
const backButton = document.getElementById("backButton");
const chatHeader = document.getElementById("chatHeader");
const chatBody = document.getElementById("chatBody");
const fullPageChatBody = document.getElementById("fullPageChatBody");
const chatInput = document.getElementById("chatInput");
const sendButton = document.getElementById("sendButton");
const fullPageInput = document.getElementById("fullPageInput");
const fullPageSendButton = document.getElementById("fullPageSendButton");
const adminStatusElement = document.getElementById("adminStatus");
const chatNotificationBadge = document.getElementById("chatNotificationBadge");

let unreadMessageCount = 0;
let isDragging = false;
let offsetX, offsetY;
let isFullScreen = false;
let initialX, initialY;

let adminIsOnline = true; // Asumsi admin online
let typingTimeout;
let isCustomerTyping = false;

let productsChat = []; // Akan diisi dari API

let negotiationState = {
  active: false,
  selectedProduct: null,
  currentOffer: 0,
};

function appendMessage(sender, message, containerId) {
  const bodyElement = document.getElementById(containerId);
  if (!bodyElement) {
    console.error(`Elemen dengan ID '${containerId}' tidak ditemukan.`);
    return;
  }

  const messageDiv = document.createElement("div");
  messageDiv.classList.add("message");

  if (sender === "customer" || sender === "guest") {
    messageDiv.classList.add("user-message");
  } else if (sender === "bot" || sender === "user") {
    messageDiv.classList.add("bot-message");
  }

  messageDiv.innerHTML = message;
  bodyElement.appendChild(messageDiv);
  bodyElement.scrollTop = bodyElement.scrollHeight;
}

function addTypingIndicatorBubble(containerId, senderType) {
  const bodyElement = document.getElementById(containerId);
  if (!bodyElement) return;

  const existingTypingBubble = bodyElement.querySelector(".typing-bubble");
  if (existingTypingBubble) {
    if (existingTypingBubble.dataset.senderType === senderType) {
      existingTypingBubble.classList.remove("shrink-out");
      return;
    } else {
      existingTypingBubble.remove();
    }
  }

  const typingBubble = document.createElement("div");
  typingBubble.classList.add("message", "typing-bubble");
  typingBubble.dataset.senderType = senderType;

  if (senderType === "user" || senderType === "bot") {
    typingBubble.classList.add("bot-message");
  } else if (senderType === "customer" || senderType === "guest") {
    typingBubble.classList.add("user-message");
  }

  typingBubble.innerHTML = `<span class="dot"></span><span class="dot"></span><span class="dot"></span>`;
  bodyElement.appendChild(typingBubble);
  bodyElement.scrollTop = bodyElement.scrollHeight;
}

function removeTypingIndicatorBubble(containerId) {
  const bodyElement = document.getElementById(containerId);
  if (!bodyElement) return;

  const typingBubble = bodyElement.querySelector(".typing-bubble");
  if (typingBubble) {
    typingBubble.classList.add("shrink-out");
    setTimeout(() => {
      typingBubble.remove();
    }, 300);
  }
}

// Override appendChild untuk menghapus typing indicator saat pesan baru ditambahkan
const originalAppendChild = Node.prototype.appendChild;
Node.prototype.appendChild = function(newNode) {
  if ((this === chatBody || this === fullPageChatBody) && newNode.classList.contains("message")) {
    if (!newNode.classList.contains("typing-bubble") && !newNode.classList.contains("user-message")) {
      removeTypingIndicatorBubble(this.id);
    } else if (newNode.classList.contains("user-message")) {
      removeTypingIndicatorBubble(this.id);
    }
  }
  return originalAppendChild.call(this, newNode);
};

function attachPromoCardListeners() {
  const attachListeners = (containerId) => {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.querySelectorAll(".promo-card").forEach((card) => {
      if (!card.classList.contains('product-option')) {
        card.onclick = null; // Hapus listener sebelumnya untuk mencegah duplikasi
        card.onclick = async () => {
          const target = card.dataset.target;
          const userMessageContent = card.querySelector("h4") ?
            card.querySelector("h4").textContent :
            card.textContent.trim();

          appendMessage(isCustomerLoggedIn ? "customer" : "guest", userMessageContent, chatBody.id);
          appendMessage(isCustomerLoggedIn ? "customer" : "guest", userMessageContent, fullPageChatBody.id);

          await sendChatMessageToBackend(userMessageContent, isCustomerLoggedIn ? "customer" : "guest");
          handleBotResponse(target);
        };
      }
    });
    attachNegotiationListeners(containerId);
  };

  attachListeners(chatBody.id);
  attachListeners(fullPageChatBody.id);
}

function createOfferInput(containerId) {
  const chatBodyElement = document.getElementById(containerId);
  if (!chatBodyElement) return;

  const existingInput = chatBodyElement.querySelector(".offer-input");
  if (existingInput) {
    existingInput.remove();
  }

  const inputContainer = document.createElement("div");
  inputContainer.className = "offer-input";
  inputContainer.innerHTML = `
<input type="text" id="offerInput" placeholder="nominal penawaran">
<button id="submitOffer">Ajukan Penawaran</button>
`;

  chatBodyElement.appendChild(inputContainer);
  console.log("Input penawaran dibuat.");
  attachNegotiationListeners(containerId);
  chatBodyElement.scrollTop = chatBodyElement.scrollHeight;
}

function formatRupiahInput(event) {
  let input = event.target.value;
  input = input.replace(/[^0-9]/g, "");

  if (input === "" || input === "0") {
    event.target.value = "";
    return;
  }

  let number = parseInt(input, 10);
  if (isNaN(number)) {
    event.target.value = "";
    return;
  }

  event.target.value = new Intl.NumberFormat("id-ID").format(number);
}

// partials/chat/js/koneksi-api-chat.js
// (Ini adalah bagian dari koneksi-api-chat.js yang digabungkan)

async function updateUnreadMessageCount() {
  try {
    // Path API diperbaiki menjadi absolut dari root web server
    const response = await fetch(`/app_customer/api/chat.php?action=get_unread_count&session_id=${chatSessionId}`);
    const data = await response.json();

    if (data.success) {
      unreadMessageCount = data.unread_count;

      if (unreadMessageCount > 0 && !chatPanel.classList.contains("active")) {
        chatNotificationBadge.textContent = unreadMessageCount;
        chatNotificationBadge.classList.add("active");
      } else {
        chatNotificationBadge.textContent = 0;
        chatNotificationBadge.classList.remove("active");
      }
    } else {
      console.error("Gagal mendapatkan jumlah pesan belum dibaca:", data.message);
      chatNotificationBadge.textContent = 0;
      chatNotificationBadge.classList.remove("active");
    }

  } catch (error) {
    console.error("Kesalahan jaringan saat mendapatkan jumlah pesan belum dibaca:", error);
    chatNotificationBadge.textContent = 0;
    chatNotificationBadge.classList.remove("active");
  }
}

async function markMessagesAsRead() {
  if (!chatSessionId) return;

  try {
    // Path API diperbaiki menjadi absolut dari root web server
    const response = await fetch("/app_customer/api/chat.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        action: "mark_as_read_customer",
        session_id: chatSessionId,
      }),
    });
    const data = await response.json();
    if (data.success) {
      console.log("Pesan ditandai sudah dibaca oleh customer.");
      await updateUnreadMessageCount();
    } else {
      console.error("Gagal menandai pesan sudah dibaca oleh customer:", data.message);
    }
  } catch (error) {
    console.error("Kesalahan jaringan saat menandai pesan sudah dibaca:", error);
  }
}

chatButton.addEventListener("click", async () => {
  chatPanel.classList.toggle("active");

  if (chatPanel.classList.contains("active")) {
    await getOrCreateChatSession();
    await loadChatHistory();
    adminStatusElement.textContent = "Online";
    adminStatusElement.classList.remove("offline");
    adminStatusElement.classList.add("online");
    await markMessagesAsRead();
    await updateUnreadMessageCount();
  }
});

backButton.addEventListener("click", () => {
  chatBody.innerHTML = fullPageChatBody.innerHTML;
  attachPromoCardListeners();
  setTimeout(() => {
    chatBody.scrollTop = chatBody.scrollHeight;
  }, 0);

  chatPanel.classList.remove("fullscreen");
  isFullScreen = false;
  sendTypingStatus(false);
  removeTypingIndicatorBubble(chatBody.id);
  removeTypingIndicatorBubble(fullPageChatBody.id);
});

chatHeader.addEventListener("mousedown", (e) => {
  if (chatPanel.classList.contains("fullscreen")) return;

  isDragging = true;
  const rect = chatPanel.getBoundingClientRect();
  offsetX = e.clientX - rect.left;
  offsetY = e.clientY - rect.top;

  initialX = rect.left;
  initialY = rect.top;

  chatPanel.style.position = "fixed";
  chatPanel.style.left = rect.left + "px";
  chatPanel.style.top = rect.top + "px";
  chatPanel.style.bottom = "auto";
  chatPanel.style.right = "auto";
  chatPanel.style.transition = "none";
  e.preventDefault();
});

document.addEventListener("mousemove", (e) => {
  if (isDragging && !chatPanel.classList.contains("fullscreen")) {
    const newX = e.clientX - offsetX;
    const newY = e.clientY - offsetY;
    chatPanel.style.left = newX + "px";
    chatPanel.style.top = newY + "px";
  }
});

document.addEventListener("mouseup", () => {
  if (isDragging) {
    isDragging = false;
    const rect = chatPanel.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    let newX = parseFloat(chatPanel.style.left);
    let newY = parseFloat(chatPanel.style.top);

    let needsAdjustment = false;
    if (newX < 0) {
      newX = 0;
      needsAdjustment = true;
    }
    if (newX + rect.width > viewportWidth) {
      newX = viewportWidth - rect.width;
      needsAdjustment = true;
    }
    if (newY < 0) {
      newY = 0;
      needsAdjustment = true;
    }
    if (newY + rect.height > viewportHeight) {
      newY = viewportHeight - rect.height;
      needsAdjustment = true;
    }

    if (needsAdjustment) {
      chatPanel.style.left = newX + "px";
      chatPanel.style.top = newY + "px";
      initialX = newX;
      initialY = newY;
    }
  }
});

closeBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  chatPanel.classList.remove("active");
});

fullscreenBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  isFullScreen = !isFullScreen;

  if (isFullScreen) {
    fullPageChatBody.innerHTML = chatBody.innerHTML;
    attachPromoCardListeners();
    setTimeout(() => {
      fullPageChatBody.scrollTop = fullPageChatBody.scrollHeight;
    }, 0);

    chatPanel.style.transition =
      "all 0.4s ease-in-out, transform 0.4s ease-in-out";
    chatPanel.style.transformOrigin = "bottom right";
    chatPanel.style.transform = "scale(1.05)";
    setTimeout(() => {
      chatPanel.classList.add("fullscreen");
      chatPanel.style.transform = "scale(1)";
      chatPanel.style.left = "0";
      chatPanel.style.top = "0";
      chatPanel.style.bottom = "0";
      chatPanel.style.right = "0";
    }, 50);
    fullscreenBtn.innerHTML = `<span>
<svg id="minimize" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#ffffff" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title></title> <g id="Complete"> <g id="minimize"> <g> <path d="M8,3V6A2,2,0,0,1,6,8H3" fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> <path d="M16,21V18a2,2,0,0,1,2-2h3" fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> <path d="M8,21V18a2,2,0,0,0-2-2H3" fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> <path d="M16,3V6a2,2,0,0,0,2,2h3" fill="none" stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> </g> </g> </g> </g></svg>
</span>`;
  } else {
    chatBody.innerHTML = fullPageChatBody.innerHTML;
    attachPromoCardListeners();
    setTimeout(() => {
      chatBody.scrollTop = chatBody.scrollHeight;
    }, 0);

    chatPanel.style.transition =
      "all 0.4s ease-in-out, transform 0.4s ease-in-out";
    chatPanel.style.transformOrigin = "bottom right";
    chatPanel.style.transform = "scale(0.95)";
    setTimeout(() => {
      chatPanel.classList.remove("fullscreen");
      chatPanel.style.left = initialX + "px";
      chatPanel.style.top = initialY + "px";
      chatPanel.style.bottom = "auto";
      chatPanel.style.right = "auto";
      chatPanel.style.transform = "scale(1)";
    }, 300);
    fullscreenBtn.innerHTML = `<span>
<svg id="iconFullscreenBtn" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="white">
<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier">
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
</g>
</svg>
</span>`;
  }
});

window.addEventListener("resize", () => {
  if (
    !chatPanel.classList.contains("active") ||
    chatPanel.classList.contains("fullscreen")
  )
    return;

  const rect = chatPanel.getBoundingClientRect();
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;

  let newX = parseFloat(chatPanel.style.left) || initialX || 0;
  let newY = parseFloat(chatPanel.style.top) || initialY || 0;

  let needsAdjustment = false;
  if (newX < 0) {
    newX = 0;
    needsAdjustment = true;
  }
  if (newX + rect.width > viewportWidth) {
    newX = viewportWidth - rect.width;
    needsAdjustment = true;
  }
  if (newY < 0) {
    newY = 0;
    needsAdjustment = true;
  }
  if (newY + rect.height > viewportHeight) {
    newY = viewportHeight - rect.height;
    needsAdjustment = true;
  }

  if (needsAdjustment) {
    chatPanel.style.left = newX + "px";
    chatPanel.style.top = newY + "px";
    initialX = newX;
    initialY = newY;
  }
});

window.addEventListener("load", () => {
  const rect = chatPanel.getBoundingClientRect();
  initialX = rect.left;
  initialY = rect.top;
  attachPromoCardListeners();
});

async function handleBotResponse(target) {
  let botMessage = "";

  switch (target) {
    case "about":
      botMessage =
        "Pondasikita.com adalah platform e-commerce multi-vendor terkemuka yang menyediakan berbagai jenis bahan bangunan berkualitas tinggi, dari semen hingga keramik, langsung dari toko terdekat Anda. Kami berkomitmen memberikan pelayanan terbaik dan harga kompetitif. Kunjungi website kami untuk info lebih lanjut!";
      break;
    case "products":
      botMessage =
        "Anda bisa mencari produk bahan bangunan dengan mengetikkan nama produk (misalnya 'semen', 'cat tembok', 'batu') atau kategori (misalnya 'keramik', 'pipa'). Saya akan mencoba membantu Anda menemukan produk yang relevan dari toko-toko terdekat. Atau Anda dapat mengunjungi halaman produk di website utama kami untuk filter yang lebih lengkap.";
      break;
    case "payment":
      botMessage =
        "Pondasikita.com menyediakan berbagai metode pembayaran yang mudah dan aman, termasuk transfer bank (virtual account), dompet digital (e-wallet), dan kartu kredit melalui payment gateway terpercaya. Anda bisa memilih metode yang paling nyaman saat checkout.";
      break;
    case "delivery":
      botMessage =
        "Pondasikita.com adalah platform online yang menghubungkan Anda dengan toko-toko bahan bangunan terdekat. Pengiriman barang dapat dilakukan melalui opsi 'Pengiriman oleh Toko' yang memanfaatkan armada toko sendiri untuk pengiriman cepat dan terjangkau (bahkan same-day delivery untuk wilayah Jabodetabek), atau melalui kurir pihak ketiga untuk barang tertentu.";
      break;
    default:
      botMessage =
        "Maaf, saya tidak mengerti pilihan Anda. Silakan pilih dari opsi yang tersedia atau ketik pesan Anda.";
  }

  appendMessage("bot", botMessage, chatBody.id);
  appendMessage("bot", botMessage, fullPageChatBody.id);
  await sendChatMessageToBackend(botMessage, "bot");
}

document.addEventListener("DOMContentLoaded", attachPromoCardListeners);

async function sendMessage(inputElement, chatBodyElement) {
  const message = inputElement.value.trim();
  if (message === "") return;

  await getOrCreateChatSession();

  if (!chatSessionId) {
      appendMessage(
          "bot",
          "Gagal mengirim pesan: Sesi chat tidak dapat dibentuk. Mohon periksa koneksi Anda atau coba lagi nanti.",
          chatBodyElement.id
      );
      return;
  }

  sendTypingStatus(false);

  const currentSenderType = isCustomerLoggedIn ? "customer" : "guest";

  appendMessage(currentSenderType, message, chatBody.id);
  appendMessage(currentSenderType, message, fullPageChatBody.id);

  inputElement.value = "";

  addTypingIndicatorBubble(chatBody.id, "bot");
  addTypingIndicatorBubble(fullPageChatBody.id, "bot");

  const sendResult = await sendChatMessageToBackend(message, currentSenderType);

  removeTypingIndicatorBubble(chatBody.id);
  removeTypingIndicatorBubble(fullPageChatBody.id);

  if (sendResult && sendResult.success && sendResult.ai_messages) {
    sendResult.ai_messages.forEach((aiMsg) => {
      appendMessage(aiMsg.sender_type, aiMsg.message_text, chatBody.id);
      appendMessage(aiMsg.sender_type, aiMsg.message_text, fullPageChatBody.id);
    });
    attachPromoCardListeners();
  } else if (sendResult && !sendResult.success) {
      appendMessage("bot", `Terjadi masalah saat mengirim pesan: ${sendResult.message}`, chatBody.id);
      appendMessage("bot", `Terjadi masalah saat mengirim pesan: ${sendResult.message}`, fullPageChatBody.id);
  }
}

sendButton.addEventListener("click", () => sendMessage(chatInput, chatBody));
chatInput.addEventListener("keypress", (e) => {
  if (e.key === "Enter") {
    sendMessage(chatInput, chatBody);
  }
});

fullPageSendButton.addEventListener("click", () =>
  sendMessage(fullPageInput, fullPageChatBody)
);
fullPageInput.addEventListener("keypress", (e) => {
  if (e.key === "Enter") {
    sendMessage(fullPageInput, fullPageChatBody);
  }
});

let lastInputTime = 0;
const TYPING_THRESHOLD_MS = 50;
const IDLE_TIMEOUT_MS = 1500;

function handleInput(event) {
  if (!chatSessionId) return;

  const currentTime = Date.now();
  if (currentTime - lastInputTime > TYPING_THRESHOLD_MS || !isCustomerTyping) {
    sendTypingStatus(true);
  }
  lastInputTime = currentTime;

  clearTimeout(typingTimeout);
  typingTimeout = setTimeout(() => {
    sendTypingStatus(false);
  }, IDLE_TIMEOUT_MS);
}

chatInput.addEventListener("input", handleInput);
fullPageInput.addEventListener("input", handleInput);

function attachNegotiationListeners(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const searchInput = container.querySelector("#productSearchInput");
  if (searchInput) {
    searchInput.oninput = null;
    searchInput.oninput = (e) =>
      filterProducts(e.target.value, containerId);
  }

  const clearSearchBtn = container.querySelector("#clearSearchButton");
  if (clearSearchBtn) {
    clearSearchBtn.onclick = null;
    clearSearchBtn.onclick = () => {
      if (searchInput) {
        searchInput.value = "";
        filterProducts("", containerId);
        searchInput.focus();
      }
    };
  }

  container.querySelectorAll(".product-option").forEach((option) => {
    option.onclick = null;
    option.onclick = () => {
      const productId = option.getAttribute("data-id");
      selectProductForRedirection(productId);
    };
  });

  container.querySelectorAll(".negotiation-btn").forEach((btn) => {
    btn.onclick = null;
    btn.onclick = async () => {
      const action = btn.dataset.action;
      const productId = btn.dataset.productId;
      const storeId = btn.dataset.storeId;

      if (action === "contactStore") {
        const redirectUrl = `/toko/${storeId}/chat`;
        appendMessage("bot", `Baik, Anda akan dialihkan untuk menghubungi toko langsung. Silakan klik <a href="${redirectUrl}" target="_blank">di sini</a>.`, chatBody.id);
        appendMessage("bot", `Baik, Anda akan dialihkan untuk menghubungi toko langsung. Silakan klik <a href="${redirectUrl}" target="_blank">di sini</a>.`, fullPageChatBody.id);
        console.log(`Redirecting to store chat for store ID: ${storeId}`);
      } else if (action === "continueTransaction") {
        const redirectUrl = `/produk/${productId}`;
        appendMessage("bot", `Baik, Anda akan dialihkan ke halaman produk untuk melanjutkan transaksi. Silakan klik <a href="${redirectUrl}" target="_blank">di sini</a>.`, chatBody.id);
        appendMessage("bot", `Baik, Anda akan dialihkan ke halaman produk untuk melanjutkan transaksi. Silakan klik <a href="${redirectUrl}" target="_blank">di sini</a>.`, fullPageChatBody.id);
        console.log(`Redirecting to product page for product ID: ${productId}`);
      } else if (action === "tryAgain") {
        const msg = "Silakan coba tanyakan kembali, atau pilih opsi lain:";
        appendMessage("bot", msg, chatBody.id);
        appendMessage("bot", msg, fullPageChatBody.id);
        await sendChatMessageToBackend(msg, isCustomerLoggedIn ? "customer" : "guest");
      } else if (action === "selectOtherProduct") {
        startProductInquiry();
      }
    };
  });

  const submitOfferBtn = container.querySelector("#submitOffer");
  if (submitOfferBtn) {
    submitOfferBtn.onclick = null;
    submitOfferBtn.onclick = () => submitOffer(containerId);
  }

  const offerInputElem = container.querySelector("#offerInput");
  if (offerInputElem) {
    offerInputElem.onkeypress = null;
    offerInputElem.onkeypress = (e) => {
      if (e.key === "Enter") {
        submitOffer(containerId);
      }
    };
    offerInputElem.oninput = formatRupiahInput;
  }
}

function filterProducts(searchTerm, containerId) {
  const container = document.getElementById(containerId);
  const productListContainer = container.querySelector(
    "#negotiableProductListContainer"
  );
  const noResultsMessage = container.querySelector("#noProductResultsMessage");
  const clearSearchButton = container.querySelector("#clearSearchButton");

  if (!productListContainer) return;

  let visibleCount = 0;
  const lowerCaseSearchTerm = searchTerm.toLowerCase();

  if (lowerCaseSearchTerm.length > 0) {
    if (clearSearchButton) clearSearchButton.style.display = "inline-block";
  } else {
    if (clearSearchButton) clearSearchButton.style.display = "none";
  }

  productListContainer.querySelectorAll(".product-option").forEach((option) => {
    const productName = option.dataset.productName.toLowerCase();
    if (productName.includes(lowerCaseSearchTerm)) {
      option.style.display = "";
      visibleCount++;
    } else {
      option.style.display = "none";
    }
  });

  if (noResultsMessage) {
    if (visibleCount === 0 && lowerCaseSearchTerm.length > 0) {
      noResultsMessage.style.display = "block";
    } else {
      noResultsMessage.style.display = "none";
    }
  }
}

async function startProductInquiry() {
  negotiationState.active = true;
  negotiationState.selectedProduct = null;
  negotiationState.currentOffer = 0;

  if (productsChat.length === 0) {
    await fetchProductsChat();
    if (productsChat.length === 0) {
      const msg =
        "Maaf, tidak ada produk bahan bangunan yang tersedia saat ini.";
      appendMessage("bot", msg, chatBody.id);
      appendMessage("bot", msg, fullPageChatBody.id);
      await sendChatMessageToBackend(msg, "bot");
      return;
    }
  }

  const searchInputHtml = `
      <div class="search-container">
          <input type="text" id="productSearchInput" placeholder="Cari produk...">
          <button id="clearSearchButton" style="display:none;">X</button>
      </div>
      <p id="noProductResultsMessage" style="display:none; color: gray; text-align: center; margin-top: 10px;">Tidak ada produk ditemukan.</p>
      <div id="negotiableProductListContainer">
          ${productsChat
            .map(
              (product) =>
                `<div class="promo-card product-option" data-id="${
                  product.product_id
                }" data-product-name="${product.display_name}">
                    <h4>${product.display_name}</h4>
                    <img class="promo-card-img" src="${product.image}" alt="${
                  product.brand_name
                } ${product.product_name}" >
                    <p>Harga: Rp${Number(product.price).toLocaleString(
                      "id-ID"
                    )}</p>
                    <p>Toko: ${product.store_name}</p>
                </div>`
            )
            .join("")}
      </div>
    `;

  const messageHtml = `<strong>Berikut beberapa produk terbaru/populer di Pondasikita.com. Pilih produk untuk melihat detail, atau ketik nama produk untuk mencari:</strong>${searchInputHtml}`;

  appendMessage("bot", messageHtml, chatBody.id);
  appendMessage("bot", messageHtml, fullPageChatBody.id);
  await sendChatMessageToBackend(messageHtml, "bot");

  attachNegotiationListeners(chatBody.id);
  attachNegotiationListeners(fullPageChatBody.id);
}

function selectProductForRedirection(productId) {
    const product = productsChat.find((p) => p.product_id === productId);
    if (!product) return;

    const messageHtml = `
        <strong>Anda memilih ${product.display_name}.</strong>
        <p>Untuk detail lebih lanjut atau untuk melakukan pembelian/negosiasi, silakan kunjungi halaman produk ini di website kami atau hubungi langsung toko penjual.</p>
        <button class="negotiation-btn" data-action="continueTransaction" data-product-id="${productId}">Lihat Produk di Website</button>
        <button class="negotiation-btn" data-action="contactStore" data-store-id="${product.store_id}">Hubungi Toko ${product.store_name}</button>
    `;

    appendMessage("bot", messageHtml, chatBody.id);
    appendMessage("bot", messageHtml, fullPageChatBody.id);
    sendChatMessageToBackend(messageHtml, "bot");
    attachNegotiationListeners(chatBody.id);
    attachNegotiationListeners(fullPageChatBody.id);
}

async function submitOffer(containerId) {
    const mainContainer = document.getElementById(containerId);
    if (!mainContainer) return;

    const offerInput = mainContainer.querySelector("#offerInput");
    if (!offerInput || !negotiationState.selectedProduct) return;

    const offerAmount = Number(offerInput.value.replace(/[^0-9]/g, ""));
    if (isNaN(offerAmount) || offerAmount <= 0) {
        const msg = "Mohon masukkan angka yang valid dan lebih besar dari nol.";
        appendMessage("bot", msg, chatBody.id);
        appendMessage("bot", msg, fullPageChatBody.id);
        await sendChatMessageToBackend(msg, isCustomerLoggedIn ? "customer" : "guest");
        return;
    }

    const product = negotiationState.selectedProduct;
    const userMessage = `Saya mengajukan penawaran Rp${offerAmount.toLocaleString(
        "id-ID"
    )} untuk ${product.display_name}.`;

    document.querySelectorAll(".offer-input").forEach((el) => el.remove());

    appendMessage(isCustomerLoggedIn ? "customer" : "guest", userMessage, chatBody.id);
    appendMessage(isCustomerLoggedIn ? "customer" : "guest", userMessage, fullPageChatBody.id);

    // Path API diperbaiki menjadi absolut dari root web server
    const response = await fetch("/app_customer/api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "submit_negotiation_offer",
            session_id: chatSessionId,
            product_id: product.product_id,
            offer_amount: offerAmount,
            customer_message_text: userMessage,
            product_name: product.display_name,
        }),
    });
    const data = await response.json();

    if (data.success) {
        appendMessage("bot", data.customer_bot_response_html, chatBody.id);
        appendMessage("bot", data.customer_bot_response_html, fullPageChatBody.id);
        attachPromoCardListeners();
    } else {
        const msg = `Gagal memproses penawaran Anda: ${data.message}`;
        appendMessage("bot", msg, chatBody.id);
        appendMessage("bot", msg, fullPageChatBody.id);
        await sendChatMessageToBackend(msg, "bot");
    }
}

function resetNegotiation() {
  negotiationState.active = false;
  const msg = "Baik, mari kita mulai kembali. Ada yang bisa saya bantu?";
  appendMessage("bot", msg, chatBody.id);
  appendMessage("bot", msg, fullPageChatBody.id);
  sendChatMessageToBackend(msg, "bot");
}


document.addEventListener("DOMContentLoaded", async () => {
  await getOrCreateChatSession();
  await fetchProductsChat();
  await loadChatHistory();
  adminStatusElement.textContent = "Online";
  adminStatusElement.classList.remove("offline");
  adminStatusElement.classList.add("online");
});

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
      // Path gambar ikon juga harus absolut
      new Notification(title, {
        body: body,
        icon: "/assets/images/profile-picture/the-winner.jpeg",
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
      // Path API diperbaiki menjadi absolut dari root web server
      const response = await fetch(
        `/app_customer/api/chat.php?action=get_new_messages_customer&session_id=${chatSessionId}&is_chat_active=${isChatPanelCurrentlyActive}`
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
    // Path API diperbaiki menjadi absolut dari root web server
    const response = await fetch("/app_customer/api/chat.php?action=get_products");
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
    // Path API diperbaiki menjadi absolut dari root web server
    const response = await fetch("/app_customer/api/chat.php", {
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
      // Path API diperbaiki menjadi absolut dari root web server
      await fetch("/app_customer/api/chat.php", {
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
      // Path API diperbaiki menjadi absolut dari root web server
      const response = await fetch("/app_customer/api/chat.php", {
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
      // Path API diperbaiki menjadi absolut dari root web server
      const response = await fetch("/app_customer/api/chat.php", {
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
    // Path API diperbaiki menjadi absolut dari root web server
    const response = await fetch(
      `/app_customer/api/chat.php?action=get_history&session_id=${chatSessionId}`
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
  // Implementasi untuk mendapatkan status mengetik lawan (admin/bot) jika diperlukan
}
