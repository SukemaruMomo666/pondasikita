<?php
// live_chat_admin.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN PENTING: Sertakan file koneksi database dan config chat DI AWAL.
// Ini memastikan $koneksi dan fungsi-fungsi seperti getUserInfo() tersedia.
include('../../../config/koneksi.php'); // Path: Dari admin/kelola_data/data_barang/ ke config/koneksi.php
include('../../../api/chat/config.php'); // Path: Dari admin/kelola_data/data_barang/ ke api/chat/config.php


// ***PERBAIKAN KRITIS UNTUK ADMIN LOGIN CHECK***
// Cek apakah user sudah login dan memiliki role admin MENGGUNAKAN $_SESSION['user']
if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['level']) || $_SESSION['user']['level'] !== 'admin') {
    header('Location: ../../../auth/signin.php'); // Redirect ke halaman login jika bukan admin
    exit();
}
// ***AKHIR PERBAIKAN KRITIS***

// --- DEBUG SESI (Ini akan menampilkan isi sesi ke error log server Anda) ---
error_log("DEBUG: Isi \$_SESSION di live_chat_admin.php: " . print_r($_SESSION, true));
if (isset($_SESSION['user'])) {
    error_log("DEBUG: \$_SESSION['user']['id'] = " . ($_SESSION['user']['id'] ?? 'NULL/UNDEFINED'));
    error_log("DEBUG: \$_SESSION['user']['level'] = " . ($_SESSION['user']['level'] ?? 'NULL/UNDEFINED'));
} else {
    error_log("DEBUG: \$_SESSION['user'] is not set.");
}
// Untuk debugging langsung di browser, uncomment baris di bawah ini.
// echo "<pre>"; var_dump($_SESSION); echo "</pre>"; exit();
// --- AKHIR DEBUG SESI ---


// Pastikan koneksi database tidak null SEBELUM memanggil fungsi DB
// PERBAIKAN: Check $koneksi setelah include config/koneksi.php
if (!$koneksi) {
    die("Koneksi database gagal. Periksa config/koneksi.php dan pathnya.");
}

// Ambil data admin dari sesi (sekarang dari $_SESSION['user'])
$admin_id = $_SESSION['user']['id'] ?? null; 
$admin_name = $_SESSION['user']['nama'] ?? $_SESSION['user']['username'] ?? 'Admin'; 
$admin_level = $_SESSION['user']['level'] ?? 'customer'; 

// PERBAIKAN: Panggil getUserInfo() hanya jika $admin_id dan $koneksi valid.
// Inisialisasi $user_info ke null terlebih dahulu untuk menghindari undefined variable jika kondisi tidak terpenuhi.
$user_info = null; // Inisialisasi
if ($admin_id !== null && $koneksi) { 
    $user_info = getUserInfo($koneksi, $admin_id);
}
// PERBAIKAN: Gunakan $user_info yang sudah pasti diinisialisasi
$admin_status = $user_info['status'] ?? 'offline'; 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Live Chat - Toko Bangunan Tiga Daya</title>
    <meta name="admin-user-id-from-php" content="<?php echo htmlspecialchars($admin_id); ?>"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../../../assets/css/adminstyle.css"> 
    <link rel="stylesheet" href="../../../assets/css/admin_live_chat.css">
</head>
<body>
<div class="container-scroller">
    
    <?php include('partials/sidebar.php'); ?>
    
    <div class="page-body-wrapper">
        <div class="main-panel">
            
            <?php include('partials/navbar.php'); ?>

            <div class="content-wrapper">

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Live Chat Admin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        </div>
                </div>

                <div class="chat-admin-container">
                    <div class="chat-sidebar">
                        <div class="admin-status-control">
                            <h5>Selamat Datang, <?php echo htmlspecialchars($admin_name ?? 'Admin'); ?>!</h5>
                            <span class="status-label">Status Anda: 
                                <span id="admin-overall-status-text">
                                    <?php 
                                    $admin_status = $admin_status ?? 'offline'; // default status
                                    echo ($admin_status === 'online' || $admin_status === 'typing') ? 'Online' : 'Offline'; 
                                    ?> 
                                </span>
                                <span class="status-circle <?php echo ($admin_status === 'online' || $admin_status === 'typing') ? 'online' : 'offline'; ?>" id="admin-overall-status-circle"></span>
                            </span>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="admin-online-toggle" <?php echo ($admin_status === 'online' || $admin_status === 'typing') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="admin-online-toggle">Aktifkan Chat</label>
                            </div>
                            <small class="text-muted"><i>(Jika offline, chat dialihkan ke bot)</i></small>
                        </div>
                        
                        <h6 class="text-white mt-3 mb-2">Filter Chat</h6>
                        <ul class="nav flex-column mb-auto">
                            <li class="nav-item">
                                <a class="nav-link text-white active" href="#" data-filter="all" id="filter-all-chats"><i class="fas fa-list-alt me-2"></i> Semua Chat</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="#" data-filter="open" id="filter-open-chats"><i class="fas fa-comments me-2"></i> Chat Aktif</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="#" data-filter="pending" id="filter-pending-chats"><i class="fas fa-hourglass-half me-2"></i> Menunggu Admin</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="#" data-filter="bot" id="filter-bot-chats"><i class="fas fa-robot me-2"></i> Ditangani Bot</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="#" data-filter="closed" id="filter-closed-chats"><i class="fas fa-check-circle me-2"></i> Chat Selesai</a>
                            </li>
                        </ul>
                    </div>

                    <div class="chat-list">
                        <div class="chat-list-header">Daftar Percakapan</div>
                        <div id="active-chat-list">
                            <div class="chat-no-selection p-4 text-center">
                                <i class="fas fa-inbox"></i>
                                <p>Tidak ada chat aktif.</p>
                            </div>
                        </div>
                    </div>

                    <div class="chat-window-admin">
                        <div class="chat-messages-admin" id="chat-messages-admin">
                            <div class="chat-no-selection">
                                <i class="fas fa-comment-dots"></i>
                                <p>Pilih percakapan dari daftar untuk memulai.</p>
                            </div>
                        </div>
                        <div class="chat-input-area-admin" style="display: none;">
                            <span id="typing-indicator-customer" class="typing-indicator-admin"></span>
                            <input type="text" id="admin-chat-input" placeholder="Ketik balasan Anda...">
                            <button id="send-admin-chat-btn"><i class="fas fa-paper-plane"></i></button>
                        </div>
                        <div class="chat-actions" style="display: none;">
                            <button class="btn btn-sm btn-danger" id="end-chat-btn"><i class="fas fa-times-circle me-1"></i> Tutup Chat</button>
                            <button class="btn btn-sm btn-info" id="transfer-chat-btn" disabled><i class="fas fa-exchange-alt me-1"></i> Transfer Chat</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript untuk Admin Live Chat
        document.addEventListener('DOMContentLoaded', () => {
            let adminId = null;
            const adminIdMeta = document.querySelector('meta[name="admin-user-id-from-php"]');
            if (adminIdMeta && adminIdMeta.content) {
                const rawAdminId = adminIdMeta.content;
                const parsedId = parseInt(rawAdminId);
                if (!isNaN(parsedId)) {
                    adminId = parsedId;
                }
            }
            
            console.log("DEBUG JS: adminId from meta tag (raw):", adminIdMeta ? adminIdMeta.content : 'meta tag not found');
            console.log("DEBUG JS: adminId (parsed from meta tag):", adminId);
            console.log("DEBUG JS: Type of adminId (parsed from meta tag):", typeof adminId);

            // VERIFIKASI KRITIS: Jika adminId masih null/undefined di sini, ini adalah masalah transfer paling dasar.
            if (adminId === null || adminId === undefined) {
                console.error("CRITICAL ERROR: adminId is null/undefined in JavaScript after parsing meta tag. This indicates a severe caching or DOM issue.");
                document.getElementById('active-chat-list').innerHTML = `
                    <div class="chat-no-selection p-4 text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Kesalahan fatal: ID Admin tidak dapat dimuat di frontend. Coba hard refresh atau hubungi dukungan.</p>
                    </div>
                `;
                document.querySelector('.chat-input-area-admin').style.display = 'none';
                document.querySelector('.chat-actions').style.display = 'none';
                return;
            }

            const adminOverallStatusCircle = document.getElementById('admin-overall-status-circle');
            const adminOverallStatusText = document.getElementById('admin-overall-status-text');
            const adminOnlineToggle = document.getElementById('admin-online-toggle');
            const activeChatList = document.getElementById('active-chat-list');
            const chatMessagesAdmin = document.getElementById('chat-messages-admin');
            const adminChatInput = document.getElementById('admin-chat-input');
            const sendAdminChatBtn = document.getElementById('send-admin-chat-btn'); // PERBAIKAN: Gunakan ID yang benar
            const typingIndicatorCustomer = document.getElementById('typing-indicator-customer');
            const chatInputAreaAdmin = document.querySelector('.chat-input-area-admin');
            const chatActions = document.querySelector('.chat-actions');
            const endChatBtn = document.getElementById('end-chat-btn');

            const API_BASE_URL = '../../../api/chat/';

            let currentSelectedChatId = null;
            let lastMessageId = 0;
            let pollingIntervalAdmin = null;
            let typingTimeoutAdmin = null;
            let isTypingAdmin = false;
            const POLLING_INTERVAL_ADMIN_MS = 2000;
            const TYPING_INDICATOR_DEBOUNCE_ADMIN_MS = 1000;
            const HEARTBEAT_INTERVAL_ADMIN_MS = 15000;

            let currentChatFilter = 'all';

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
                    const response = await fetch(url, options);
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error(`HTTP error! status: ${response.status}, message: ${errorText}`);
                        throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
                    }
                    return await response.json();
                } catch (error) {
                    console.error('Fetch error in fetchData:', error);
                    return { success: false, message: error.message };
                }
            }

            async function updateAdminStatus(statusType) {
                if (adminId === null || adminId === undefined) { 
                    console.warn('Admin ID is null/undefined, cannot update status.');
                    return;
                }
                const response = await fetchData(`${API_BASE_URL}update_status.php`, 'POST', {
                    user_id: adminId,
                    status_type: statusType
                });
                if (response.success) {
                    console.log(`Admin status updated to: ${statusType}`);
                    if (statusType === 'online' || statusType === 'typing') {
                        adminOverallStatusCircle.classList.remove('offline');
                        adminOverallStatusCircle.classList.add('online');
                        adminOverallStatusText.textContent = 'Online';
                    } else {
                        adminOverallStatusCircle.classList.remove('online');
                        adminOverallStatusCircle.classList.add('offline');
                        adminOverallStatusText.textContent = 'Offline';
                    }
                } else {
                    console.error('Failed to update admin status:', response.message);
                }
            }

            function displayMessages(messages) {
                messages.forEach(msg => {
                    if (msg.id > lastMessageId) {
                        addMessageToChatAdmin(msg.message_text, msg.sender_role, msg.timestamp);
                        lastMessageId = msg.id;
                    }
                });
                chatMessagesAdmin.scrollTop = chatMessagesAdmin.scrollHeight;
            }

            async function fetchChatData() {
                if (adminId === null || adminId === undefined) { 
                    console.warn("Admin ID is null/undefined, skipping chat data fetch.");
                    activeChatList.innerHTML = `
                        <div class="chat-no-selection p-4 text-center text-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Gagal memuat chat: Admin ID tidak ditemukan. Pastikan Anda sudah login.</p>
                        </div>
                    `;
                    return;
                }

                console.log("Fetching chat list for adminId:", adminId, "filter:", currentChatFilter);
                const chatListResponse = await fetchData(`${API_BASE_URL}get_chat_list_admin.php?admin_id=${adminId}&filter=${currentChatFilter}`);
                if (chatListResponse.success) {
                    displayChatList(chatListResponse.chats);
                } else {
                    console.error('Failed to fetch chat list:', chatListResponse.message);
                    activeChatList.innerHTML = `
                        <div class="chat-no-selection p-4 text-center text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Gagal memuat daftar chat: ${chatListResponse.message}</p>
                        </div>
                    `;
                }

                if (currentSelectedChatId) {
                    console.log("Fetching chat messages for chatId:", currentSelectedChatId, "lastMessageId:", lastMessageId);
                    const chatMessagesResponse = await fetchData(`${API_BASE_URL}get_messages.php?chat_id=${currentSelectedChatId}&last_message_id=${lastMessageId}`);
                    if (chatMessagesResponse.success) {
                        displayMessages(chatMessagesResponse.messages);
                        updateCustomerTypingStatus(chatMessagesResponse.typing_status); 
                    } else {
                        console.error('Failed to fetch chat messages:', chatMessagesResponse.message);
                        chatMessagesAdmin.innerHTML = `
                            <div class="chat-no-selection text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Gagal memuat pesan: ${chatMessagesResponse.message}</p>
                            </div>
                        `;
                    }
                }
            }
            
            function startAdminPolling() {
                if (pollingIntervalAdmin) clearInterval(pollingIntervalAdmin);
                pollingIntervalAdmin = setInterval(fetchChatData, POLLING_INTERVAL_ADMIN_MS);
            }

            function stopAdminPolling() {
                if (pollingIntervalAdmin) {
                    clearInterval(pollingIntervalAdmin);
                    pollingIntervalAdmin = null;
                }
            }

            function startAdminHeartbeat() {
                if (window.adminHeartbeatInterval) clearInterval(window.adminHeartbeatInterval);
                window.adminHeartbeatInterval = setInterval(() => {
                    updateAdminStatus(isTypingAdmin ? 'typing' : 'online');
                }, HEARTBEAT_INTERVAL_ADMIN_MS);
            }

            function stopAdminHeartbeat() {
                if (window.adminHeartbeatInterval) {
                    clearInterval(window.adminHeartbeatInterval);
                    window.adminHeartbeatInterval = null;
                }
            }

            function displayChatList(chats) {
                activeChatList.innerHTML = '';
                if (chats.length === 0) {
                    activeChatList.innerHTML = `
                        <div class="chat-no-selection p-4 text-center">
                            <i class="fas fa-inbox"></i>
                            <p>Tidak ada chat untuk difilter ini.</p>
                        </div>
                    `;
                    return;
                }

                chats.forEach(chat => {
                    const chatItem = document.createElement('div');
                    chatItem.classList.add('chat-item');
                    if (chat.id === currentSelectedChatId) {
                        chatItem.classList.add('active');
                    }
                    chatItem.dataset.chatId = chat.id;

                    let statusBadgeClass = '';
                    let statusText = '';
                    if (chat.status === 'open') {
                        statusBadgeClass = 'open';
                        statusText = 'Aktif';
                    } else if (chat.status === 'pending_admin') {
                        statusBadgeClass = 'pending';
                        statusText = 'Menunggu';
                    } else if (chat.status === 'in_progress_bot') {
                        statusBadgeClass = 'bot';
                        statusText = 'Bot';
                    } else if (chat.status === 'closed') {
                        statusBadgeClass = 'closed';
                        statusText = 'Selesai';
                    }

                    // PERBAIKAN PENTING: Gunakan createElement dan textContent untuk mencegah XSS dan error parsing HTML
                    const customerNameDiv = document.createElement('div');
                    customerNameDiv.classList.add('customer-name');
                    customerNameDiv.textContent = chat.customer_name; // Menggunakan textContent

                    const lastMessageDiv = document.createElement('div');
                    lastMessageDiv.classList.add('last-message');
                    lastMessageDiv.textContent = chat.last_message_text ? chat.last_message_text : 'Belum ada pesan'; // Menggunakan textContent

                    const statusBadgeSpan = document.createElement('span');
                    statusBadgeSpan.classList.add('chat-status-badge', statusBadgeClass);
                    statusBadgeSpan.textContent = statusText; // Menggunakan textContent

                    chatItem.appendChild(customerNameDiv);
                    chatItem.appendChild(lastMessageDiv);
                    chatItem.appendChild(statusBadgeSpan);
                    
                    chatItem.addEventListener('click', () => selectChat(chat.id));
                    activeChatList.appendChild(chatItem);
                });
            }


            async function selectChat(chatId) {
                if (currentSelectedChatId === chatId) return;

                currentSelectedChatId = chatId;
                lastMessageId = 0; // Reset last message ID for new chat selection

                document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
                const selectedItem = document.querySelector(`.chat-item[data-chat-id="${chatId}"]`);
                if (selectedItem) {
                    selectedItem.classList.add('active');
                }

                chatInputAreaAdmin.style.display = 'flex';
                chatActions.style.display = 'flex';
                adminChatInput.focus();

                chatMessagesAdmin.innerHTML = ''; // Selalu bersihkan chat window saat memilih chat baru

                const response = await fetchData(`${API_BASE_URL}get_messages.php?chat_id=${currentSelectedChatId}&last_message_id=0`); 
                if (response.success) {
                    if (response.messages.length === 0) {
                        chatMessagesAdmin.innerHTML = `
                            <div class="chat-no-selection">
                                <i class="fas fa-comment-dots"></i>
                                <p>Belum ada pesan dalam percakapan ini.</p>
                            </div>
                        `;
                    } else {
                        displayMessages(response.messages); // Panggil displayMessages untuk menambahkan semua pesan awal
                    }
                    updateCustomerTypingStatus(response.typing_status); 
                    chatMessagesAdmin.scrollTop = chatMessagesAdmin.scrollHeight;
                } else {
                    console.error('Failed to load chat messages:', response.message);
                    chatMessagesAdmin.innerHTML = `
                        <div class="chat-no-selection text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Gagal memuat pesan: ${response.message}</p>
                        </div>
                    `;
                }

                const chatInfoResponse = await fetchData(`${API_BASE_URL}get_chat_info.php?chat_id=${chatId}`);
                if (chatInfoResponse.success && chatInfoResponse.chat_info) {
                    const chatStatus = chatInfoResponse.chat_info.status;
                    const chatAdminId = chatInfoResponse.chat_info.admin_id;

                    if ((chatStatus === 'pending_admin' || chatStatus === 'in_progress_bot') && chatAdminId != adminId) {
                        const takeChatResponse = await fetchData(`${API_BASE_URL}take_chat.php`, 'POST', { chat_id: chatId, admin_id: adminId });
                        if (takeChatResponse.success) {
                            console.log('Chat berhasil diambil oleh admin.');
                            fetchChatData();
                        } else {
                            console.error('Gagal mengambil chat:', takeChatResponse.message);
                        }
                    }
                }
            }

            function addMessageToChatAdmin(message, senderRole, timestamp) {
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('message-bubble');
                messageDiv.classList.add(senderRole === 'admin' ? 'admin' : 'customer');
                
                const messageText = document.createElement('p');
                messageText.textContent = message;
                messageDiv.appendChild(messageText);

                const messageInfo = document.createElement('small');
                messageInfo.classList.add('message-info');
                const date = new Date(timestamp);
                messageInfo.textContent = `${senderRole === 'admin' ? 'Anda' : (senderRole === 'bot' ? 'Bot' : 'Customer') } - ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                
                messageDiv.appendChild(messageInfo);
                chatMessagesAdmin.appendChild(messageDiv);
                chatMessagesAdmin.scrollTop = chatMessagesAdmin.scrollHeight;
            }

            async function sendAdminMessage() {
                const messageText = adminChatInput.value.trim();
                console.log('Attempting to send message:', { messageText, currentSelectedChatId, adminId });

                if (messageText === '' || !currentSelectedChatId || adminId === null || adminId === undefined) { 
                    console.warn('Cannot send message: Message text is empty, chat not selected, or admin ID is missing.');
                    alert('Gagal mengirim: Anda belum login atau chat belum dipilih.');
                    return;
                }

                adminChatInput.value = '';

                const response = await fetchData(`${API_BASE_URL}send_message.php`, 'POST', {
                    chat_id: currentSelectedChatId,
                    sender_id: adminId,
                    message_text: messageText
                });

                if (response.success) {
                    console.log('Message sent successfully from admin. Refreshing chat data...');
                    await fetchChatData();
                } else {
                    console.error('Failed to send message from admin:', response.message);
                    alert('Gagal mengirim pesan: ' + response.message);
                    adminChatInput.value = messageText;
                }
            }

            function updateCustomerTypingStatus(isCustomerTyping) {
                if (isCustomerTyping === 'typing') {
                    typingIndicatorCustomer.textContent = 'Customer sedang mengetik...';
                    typingIndicatorCustomer.classList.add('active');
                } else {
                    typingIndicatorCustomer.classList.remove('active');
                }
            }

            adminOnlineToggle.addEventListener('change', () => {
                if (adminOnlineToggle.checked) {
                    updateAdminStatus('online');
                    startAdminHeartbeat();
                } else {
                    updateAdminStatus('offline');
                    stopAdminHeartbeat();
                }
            });

            sendAdminChatBtn.addEventListener('click', sendAdminMessage);

            adminChatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendAdminMessage();
                    clearTimeout(typingTimeoutAdmin);
                    isTypingAdmin = false;
                    updateAdminStatus('online');
                    typingIndicatorCustomer.classList.remove('active');
                }
            });

            adminChatInput.addEventListener('input', () => {
                if (!isTypingAdmin) {
                    isTypingAdmin = true;
                    updateAdminStatus('typing');
                }
                clearTimeout(typingTimeoutAdmin);
                typingTimeoutAdmin = setTimeout(() => {
                    isTypingAdmin = false;
                    updateAdminStatus('online');
                }, TYPING_INDICATOR_DEBOUNCE_ADMIN_MS);
            });

            endChatBtn.addEventListener('click', async () => {
                if (!currentSelectedChatId) return;

                if (confirm('Apakah Anda yakin ingin menutup percakapan ini?')) {
                    const response = await fetchData(`${API_BASE_URL}end_chat.php`, 'POST', { chat_id: currentSelectedChatId, admin_id: adminId });
                    if (response.success) {
                        alert('Percakapan ditutup.');
                        currentSelectedChatId = null; 
                        chatMessagesAdmin.innerHTML = `
                            <div class="chat-no-selection">
                                <i class="fas fa-comment-dots"></i>
                                <p>Pilih percakapan dari daftar untuk memulai.</p>
                            </div>
                        `;
                        chatInputAreaAdmin.style.display = 'none';
                        chatActions.style.display = 'none';
                        fetchChatData(); 
                    } else {
                        alert('Gagal menutup percakapan: ' + response.message);
                    }
                }
            });

            document.getElementById('filter-all-chats').addEventListener('click', (e) => { e.preventDefault(); currentChatFilter = 'all'; fetchChatData(); updateFilterActiveClass(e.target); });
            document.getElementById('filter-open-chats').addEventListener('click', (e) => { e.preventDefault(); currentChatFilter = 'open'; fetchChatData(); updateFilterActiveClass(e.target); });
            document.getElementById('filter-pending-chats').addEventListener('click', (e) => { e.preventDefault(); currentChatFilter = 'pending'; fetchChatData(); updateFilterActiveClass(e.target); });
            document.getElementById('filter-bot-chats').addEventListener('click', (e) => { e.preventDefault(); currentChatFilter = 'bot'; fetchChatData(); updateFilterActiveClass(e.target); });
            document.getElementById('filter-closed-chats').addEventListener('click', (e) => { e.preventDefault(); currentChatFilter = 'closed'; fetchChatData(); updateFilterActiveClass(e.target); });

            function updateFilterActiveClass(clickedElement) {
                document.querySelectorAll('.chat-sidebar .nav-link').forEach(link => link.classList.remove('active'));
                clickedElement.classList.add('active');
            }

            if (adminOverallStatusCircle.classList.contains('online')) {
                adminOnlineToggle.checked = true;
                startAdminHeartbeat();
            } else {
                adminOnlineToggle.checked = false;
            }

            startAdminPolling();
        });
    </script>
</body>
</html>