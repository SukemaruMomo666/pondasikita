// assets/js/livechat.js

document.addEventListener('DOMContentLoaded', () => {
    const chatToggle = document.getElementById('live-chat-toggle');
    const chatWindow = document.getElementById('live-chat-window');
    const closeChatBtn = document.getElementById('close-chat');
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const sendChatBtn = document.getElementById('send-chat-btn');

    const preChatForm = document.getElementById('pre-chat-form'); // Akan selalu disembunyikan
    const authPromptPanel = document.getElementById('auth-prompt-panel');
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');
    const authMessage = document.getElementById('auth-message');

    const chatMainArea = document.querySelector('.chat-main-area');

    const agentStatusIndicator = document.getElementById('agent-status');
    const typingIndicator = document.getElementById('typing-indicator');

    const API_BASE_URL = '/api/chat/'; // Sesuaikan dengan path absolut yang benar (misal: '/pondasikita/api/chat/')
    let currentChatId = null;
    let lastMessageId = 0;
    let pollingInterval = null;
    let typingTimeout = null;
    let isTyping = false;
    const POLLING_INTERVAL_MS = 2000;
    const TYPING_INDICATOR_DEBOUNCE_MS = 1000;
    const HEARTBEAT_INTERVAL_MS = 15000;

    let currentUserId = null;
    let currentUserRole = 'customer'; // Role customer, bot akan dianggap sebagai 'admin' di backend

    // Helper function untuk fetch data ke API
    async function fetchData(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
        };
        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const urlWithNoCache = method === 'GET' ? `${url}?nocache=${new Date().getTime()}` : url;
            const response = await fetch(urlWithNoCache, options);
            if (!response.ok) {
                const errorText = await response.text();
                console.error(`HTTP error! status: ${response.status}, message: ${errorText}`);
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }
            const jsonResponse = await response.json();
            console.log(`Response from ${url}:`, jsonResponse);
            return jsonResponse;
        } catch (error) {
            console.error('Fetch error:', error);
            return { success: false, message: `Gagal terhubung ke server chat: ${error.message}` };
        }
    }

    // Menambahkan pesan ke tampilan chat
    function addMessageToChat(message, senderRole, messageId) {
        if (document.querySelector(`.chat-message[data-message-id="${messageId}"]`)) {
            return;
        }

        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message');
        // PERUBAHAN UNTUK AI SATU ARAH: Tentukan class CSS berdasarkan peran
        // Jika sender_role dari API adalah 'bot' atau 'admin' (karena bot diset sbg admin_id)
        // Maka tampilkan sebagai 'bot-message', selain itu 'customer-message'.
        if (senderRole === 'bot' || senderRole === 'admin') { // Bot dikirim sebagai 'admin' dari DB level
            messageDiv.classList.add('bot-message');
            messageDiv.classList.add('left'); // Bot messages on the left
        } else {
            messageDiv.classList.add('customer-message');
            messageDiv.classList.add('right'); // Customer messages on the right
        }
        
        messageDiv.textContent = message;
        messageDiv.dataset.messageId = messageId;

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Mengambil pesan baru dari server
    async function getNewMessages(isInitialLoad = false) {
        if (!currentChatId) return;

        const effectiveLastMessageId = isInitialLoad ? 0 : lastMessageId;

        const response = await fetchData(`${API_BASE_URL}get_messages.php?chat_id=${currentChatId}&last_message_id=${effectiveLastMessageId}`);

        if (response.success && response.messages) {
            if (isInitialLoad) {
                chatMessages.innerHTML = '';
                lastMessageId = 0;
            }
            response.messages.forEach(msg => {
                if (msg.id > lastMessageId || isInitialLoad) {
                    addMessageToChat(msg.message_text, msg.sender_role, msg.id);
                    if (msg.id > lastMessageId) {
                        lastMessageId = msg.id;
                    }
                }
            });
        } else {
            console.error('Failed to get new messages or response.messages is null:', response.message);
        }
        // PERUBAHAN UNTUK AI SATU ARAH: updateAgentStatus akan menampilkan status bot
        updateAgentStatus(response.agent_status, response.typing_status);
    }

    // Mengirim pesan ke server
    async function sendMessage() {
        const messageText = chatInput.value.trim();

        if (!currentUserId) {
            alert('Anda harus login untuk mengirim pesan. Silakan login atau daftar.');
            window.location.href = '/auth/signin.php'; // SESUAIKAN PATH ABSOLUT INI
            return;
        }

        if (messageText === '' || !currentChatId) return;

        chatInput.value = '';

        const response = await fetchData(`${API_BASE_URL}send_message.php`, 'POST', {
            chat_id: currentChatId,
            sender_id: currentUserId,
            message_text: messageText
        });

        if (response.success) {
            // Setelah mengirim, dapatkan pesan baru untuk menampilkan pesan user dan respons bot
            await getNewMessages();
        } else {
            console.error('Gagal mengirim pesan:', response.message);
            alert('Gagal mengirim pesan: ' + response.message + '\nSilakan coba lagi atau hubungi administrator.');
            chatInput.value = messageText; // Kembalikan pesan ke input jika gagal
        }
    }

    // Memperbarui status online/mengetik pengguna (customer)
    async function updateMyStatus(statusType) {
        if (!currentUserId) return;

        const data = {
            user_id: currentUserId,
            status_type: statusType,
            chat_id: currentChatId // Kirim chat_id juga
        };

        const response = await fetchData(`${API_BASE_URL}update_status.php`, 'POST', data);
        if (!response.success) {
            console.error('Gagal memperbarui status:', response.message);
        }
    }

    // Memperbarui tampilan status agen (sekarang bot)
    function updateAgentStatus(agentStatus, agentTypingStatus) {
        // PERUBAHAN UNTUK AI SATU ARAH: Ubah teks dan logika untuk bot
        if (agentStatus === 'online') {
            agentStatusIndicator.className = 'status-indicator online';
            agentStatusIndicator.title = 'Asisten Virtual Online';
            document.getElementById('chat-header-title').textContent = 'Live Chat (Dodo Online)';
        } else { // Jika bot tidak 'online', asumsikan sedang ada masalah
            agentStatusIndicator.className = 'status-indicator offline';
            agentStatusIndicator.title = 'Asisten Virtual Offline';
            document.getElementById('chat-header-title').textContent = 'Live Chat (Dodo Offline)';
        }

        if (agentTypingStatus && agentTypingStatus === 'typing') {
            typingIndicator.textContent = 'Dodo sedang mengetik...';
            typingIndicator.classList.add('active');
        } else {
            typingIndicator.classList.remove('active');
        }
    }

    // ============================================================================
    // KONTROL UTAMA: Ketika tombol live chat diklik
    // ============================================================================
    chatToggle.addEventListener('click', async () => {
        chatWindow.classList.toggle('active');

        if (chatWindow.classList.contains('active')) {
            // Sembunyikan semua panel untuk reset tampilan
            if (preChatForm) preChatForm.style.display = 'none';
            if (authPromptPanel) authPromptPanel.style.display = 'none';
            if (chatMainArea) chatMainArea.style.display = 'none';

            const checkUserResponse = await fetchData(`${API_BASE_URL}check_user_session.php`);

            if (checkUserResponse.success && checkUserResponse.user_id) {
                // KONDISI 1: Pengguna sudah login
                currentUserId = checkUserResponse.user_id;
                currentUserRole = checkUserResponse.user_role; // 'customer' atau 'admin'/'seller'

                // Tampilkan area chat utama
                if (chatMainArea) chatMainArea.style.display = 'block';
                const chatInputArea = document.querySelector('.chat-input-area');
                if (chatInputArea) chatInputArea.style.display = 'flex';
                if (chatMessages) chatMessages.style.display = 'flex';

                chatMessages.innerHTML = '';
                lastMessageId = 0;

                const activeChatResponse = await fetchData(`${API_BASE_URL}get_active_chat.php?user_id=${currentUserId}`);
                if (activeChatResponse.success && activeChatResponse.chat_id) {
                    currentChatId = activeChatResponse.chat_id;
                    await getNewMessages(true);
                } else {
                    // Jika tidak ada chat aktif, mulai chat baru (akan selalu dengan bot)
                    const startChatResponse = await fetchData(`${API_BASE_URL}start_chat.php`, 'POST', { customer_id: currentUserId });
                    if (startChatResponse.success && startChatResponse.chat_id) {
                        currentChatId = startChatResponse.chat_id;
                        await getNewMessages(true); // Ambil pesan pembuka dari bot
                    } else {
                        console.error('Gagal memulai chat baru:', startChatResponse.message);
                        alert('Gagal memulai chat baru: ' + startChatResponse.message);
                        // Jika gagal, tampilkan kembali prompt login/daftar sebagai fallback
                        if (chatMainArea) chatMainArea.style.display = 'none';
                        if (authPromptPanel) authPromptPanel.style.display = 'block';
                        if (authMessage) authMessage.textContent = 'Terjadi kesalahan saat memulai chat. Silakan coba lagi nanti atau login kembali.';
                        if (loginBtn) loginBtn.style.display = 'block';
                        if (registerBtn) registerBtn.style.display = 'block';
                    }
                }
                startPolling();
                startHeartbeat();
            } else {
                // KONDISI 2: Pengguna BELUM LOGIN
                if (authPromptPanel) authPromptPanel.style.display = 'block';
                if (authMessage) authMessage.textContent = 'Untuk memulai percakapan, Anda perlu login atau mendaftar.';
                if (loginBtn) loginBtn.style.display = 'block';
                if (registerBtn) registerBtn.style.display = 'block';
                if (chatMainArea) chatMainArea.style.display = 'none';

                if (loginBtn) loginBtn.onclick = () => { window.location.href = '/auth/signin.php'; }; // SESUAIKAN PATH ABSOLUT INI
                if (registerBtn) registerBtn.onclick = () => { window.location.href = '/auth/signup.php'; }; // SESUAIKAN PATH ABSOLUT INI

                stopPolling();
                stopHeartbeat();
                chatMessages.innerHTML = '';
                lastMessageId = 0;
            }
        } else {
            // KONDISI 3: Jika jendela chat ditutup
            stopPolling();
            stopHeartbeat();
            if (currentUserRole === 'customer' && currentUserId) {
                updateMyStatus('offline');
            }
            chatMessages.innerHTML = '';
            lastMessageId = 0;
        }
    });

    // Menangani tombol tutup chat
    closeChatBtn.addEventListener('click', () => {
        chatWindow.classList.remove('active');
        stopPolling();
        stopHeartbeat();
        if (currentUserRole === 'customer' && currentUserId) {
            updateMyStatus('offline');
        }
        chatMessages.innerHTML = '';
        lastMessageId = 0;
    });

    sendChatBtn.addEventListener('click', sendMessage);

    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!currentUserId) {
                alert('Anda harus login untuk mengirim pesan. Silakan login atau daftar.');
                window.location.href = '/auth/signin.php'; // SESUAIKAN PATH ABSOLUT INI
                return;
            }
            sendMessage();
            clearTimeout(typingTimeout);
            isTyping = false;
            updateMyStatus('online');
            typingIndicator.classList.remove('active');
        }
    });

    chatInput.addEventListener('input', () => {
        if (!currentUserId) {
            return;
        }
        if (!isTyping) {
            isTyping = true;
            updateMyStatus('typing');
        }
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            isTyping = false;
            updateMyStatus('online');
        }, TYPING_INDICATOR_DEBOUNCE_MS);
    });

    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => getNewMessages(false), POLLING_INTERVAL_MS);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    function startHeartbeat() {
        if (window.heartbeatInterval) clearInterval(window.heartbeatInterval);
        window.heartbeatInterval = setInterval(() => {
            if (currentUserId) {
                updateMyStatus('online');
            }
        }, HEARTBEAT_INTERVAL_MS);
    }

    function stopHeartbeat() {
        if (window.heartbeatInterval) {
            clearInterval(window.heartbeatInterval);
            window.heartbeatInterval = null;
        }
    }
});