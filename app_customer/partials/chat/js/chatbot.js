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

    const response = await fetch("./api/chat.php", {
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