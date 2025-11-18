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

let adminIsOnline = true;
let typingTimeout;
let isCustomerTyping = false;

let productsChat = [];

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
        card.onclick = null;
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

async function updateUnreadMessageCount() {
  try {
    const response = await fetch(`../api/chat.php?action=get_unread_count&session_id=${chatSessionId}`);
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
    const response = await fetch("../api/chat.php", {
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