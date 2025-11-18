<?php
session_start();
require_once '../config/koneksi.php';

// Keamanan
if (!isset($_SESSION['logged_in']) || $_SESSION['level'] !== 'seller') {
    header("Location: index.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Manajemen Chat - Seller Center</title>
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/template/spica/template/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/template/spica/template/css/style.css">
    <style>
        .chat-container { display: flex; height: 80vh; border: 1px solid #dee2e6; background: #fff; }
        .chat-list-pane { width: 30%; border-right: 1px solid #dee2e6; overflow-y: auto; }
        .chat-window-pane { width: 70%; display: flex; flex-direction: column; }
        .chat-list-item { padding: 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .chat-list-item:hover, .chat-list-item.active { background-color: #f8f9fa; }
        .chat-list-item h6 { margin: 0; font-weight: 600; }
        .chat-list-item p { margin: 0; font-size: 0.9rem; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-header { padding: 15px; border-bottom: 1px solid #dee2e6; font-weight: 600; }
        .message-container { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column-reverse; }
        .message { max-width: 70%; padding: 10px 15px; border-radius: 18px; margin-bottom: 10px; }
        .message.customer { background-color: #e9ecef; align-self: flex-start; }
        .message.seller { background-color: #d1e7dd; align-self: flex-end; }
        .chat-input-form { padding: 15px; border-top: 1px solid #dee2e6; display: flex; gap: 10px; }
    </style>
</head>
<body>
<div class="container-scroller">
    <?php include 'partials/sidebar.php'; ?>
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title"><i class="mdi mdi-forum"></i> Manajemen Chat</h3>
                </div>
                <div class="card">
                    <div class="card-body p-0">
                        <div class="chat-container">
                            <div class="chat-list-pane" id="chat-list-container">
                                </div>
                            <div class="chat-window-pane">
                                <div id="chat-window-placeholder" class="d-flex h-100 justify-content-center align-items-center text-muted">
                                    <p>Pilih percakapan untuk memulai.</p>
                                </div>
                                <div id="chat-window-main" class="d-none h-100 d-flex flex-column">
                                    <div class="chat-header" id="chat-window-header"></div>
                                    <div class="message-container" id="message-container"></div>
                                    <form class="chat-input-form" id="message-form">
                                        <input type="hidden" name="chat_id" id="active_chat_id">
                                        <input type="text" name="message_text" class="form-control" placeholder="Ketik balasan..." required>
                                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-send"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/template/spica/template/vendors/js/vendor.bundle.base.js"></script>
<script>
// Logika Chat yang kompleks
$(document).ready(function() {
    let activeChatId = null;
    let pollingInterval = null;

    // Memuat daftar chat di panel kiri
    function loadChatList() {
        $.getJSON('../actions/api_chat.php?action=get_chat_list', function(data) {
            $('#chat-list-container').empty();
            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach(chat => {
                    const chatItem = `
                        <div class="chat-list-item" data-chat-id="${chat.id}" data-customer-name="${chat.nama_pelanggan}">
                            <h6>${chat.nama_pelanggan}</h6>
                            <p>${chat.last_message || '...'}</p>
                        </div>`;
                    $('#chat-list-container').append(chatItem);
                });
            }
        });
    }

    // Memuat pesan untuk chat yang dipilih
    function loadMessages(chatId) {
        if (!chatId) return;
        $.getJSON(`../actions/api_chat.php?action=get_messages&chat_id=${chatId}`, function(data) {
            $('#message-container').empty();
            if (data.status === 'success') {
                data.data.forEach(msg => {
                    const msgClass = msg.sender_id == <?=$_SESSION['user_id']?> ? 'seller' : 'customer';
                    const msgHtml = `<div class="message ${msgClass}">${msg.message_text}</div>`;
                    $('#message-container').prepend(msgHtml);
                });
            }
        });
    }

    // Event handler saat chat di panel kiri diklik
    $('#chat-list-container').on('click', '.chat-list-item', function() {
        activeChatId = $(this).data('chat-id');
        const customerName = $(this).data('customer-name');

        $('.chat-list-item').removeClass('active');
        $(this).addClass('active');

        $('#chat-window-placeholder').addClass('d-none');
        $('#chat-window-main').removeClass('d-none');
        $('#chat-window-header').text(customerName);
        $('#active_chat_id').val(activeChatId);
        
        loadMessages(activeChatId);

        // Mulai polling untuk chat ini
        if(pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => loadMessages(activeChatId), 5000); // Cek pesan baru setiap 5 detik
    });

    // Event handler saat mengirim pesan
    $('#message-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post('../actions/api_chat.php?action=send_message', formData, function(data) {
            if (data.status === 'success') {
                $('input[name="message_text"]').val('');
                loadMessages(activeChatId); // Langsung muat ulang pesan setelah mengirim
            }
        }, 'json');
    });

    // Muat daftar chat saat halaman pertama kali dibuka
    loadChatList();
});
</script>
</body>
</html>