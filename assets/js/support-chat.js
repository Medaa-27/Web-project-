(function() {
    const config = window.supportChatConfig || {};
    const ticketId = Number(config.ticketId || 0);
    const userId = Number(config.userId || 0);
    const userRole = config.userRole || '';
    const getMessagesUrl = config.getMessagesUrl || '/api/get_messages.php';
    const sendMessageUrl = config.sendMessageUrl || '/api/send_message.php';
    const editMessageUrl = config.editMessageUrl || '../api/edit_message.php';
    const deleteMessageUrl = config.deleteMessageUrl || '../api/delete_message.php';
    const baseUrl = config.baseUrl || '/';
    const initialMessages = config.initialMessages || [];
    const pollingInterval = 2500;

    const chatBox = document.getElementById('chatBox');
    const form = document.getElementById('supportMessageForm');
    const statusEl = document.getElementById('supportMessageStatus');
    const replyInput = form ? form.querySelector('input[name="reply_to"]') : null;
    const replyPreviewContainer = document.getElementById('replyPreview');
    const messageCache = {};

    // Initialize cache with initial messages
    if (Array.isArray(initialMessages)) {
        initialMessages.forEach(m => {
            messageCache[m.message_id] = m;
        });
    }

    if (!ticketId || !chatBox || !form) {
        return;
    }

    function setStatus(message, isError) {
        if (!statusEl) return;
        statusEl.textContent = message;
        statusEl.className = isError ? 'small text-danger mt-2' : 'small text-muted mt-2';
        statusEl.classList.remove('d-none');
    }

    function clearStatus() {
        if (!statusEl) return;
        statusEl.textContent = '';
        statusEl.classList.add('d-none');
    }

    function getLastMessageId() {
        const items = chatBox.querySelectorAll('[data-message-id]');
        if (!items.length) {
            return 0;
        }
        const last = items[items.length - 1];
        return Number(last.dataset.messageId || 0);
    }

    function createMessageElement(message) {
        if (!message || typeof message !== 'object') {
            return null;
        }

        const mine = Number(message.sender_id) === userId && message.sender_role === userRole;
        const wrapper = document.createElement('div');
        wrapper.className = `mb-3 d-flex ${mine ? 'justify-content-end' : 'justify-content-start'}`;
        wrapper.dataset.messageId = message.message_id;

        const bubble = document.createElement('div');
        bubble.className = 'p-3 rounded';
        bubble.style.maxWidth = '80%';
        bubble.style.background = mine ? '#e7f1ff' : '#f1f3f5';

        const author = document.createElement('div');
        author.className = 'small fw-semibold mb-1';
        author.textContent = mine ? 'You' : (message.full_name || capitalizeRole(message.sender_role));

        const body = document.createElement('div');

        if (message.is_deleted) {
            const deletedLabel = document.createElement('div');
            deletedLabel.className = 'fst-italic text-muted';
            deletedLabel.textContent = 'This message was deleted.';
            body.appendChild(deletedLabel);
        } else {
            if (message.reply_to) {
                const replyPreview = messageCache[message.reply_to];
                const replyBox = document.createElement('div');
                replyBox.className = 'border-start ps-2 small text-muted mb-2';
                replyBox.textContent = 'Replying to: ' + (replyPreview ? (replyPreview.message || (replyPreview.file_path ? 'Attachment' : '')) : 'Message');
                body.appendChild(replyBox);
            }

            const messageText = document.createElement('div');
            const textLines = (message.message || '').split('\n');
            textLines.forEach((line, index) => {
                messageText.appendChild(document.createTextNode(line));
                if (index < textLines.length - 1) {
                    messageText.appendChild(document.createElement('br'));
                }
            });
            body.appendChild(messageText);

            if (message.file_path) {
                const normalizedPath = message.file_path.replace(/^\/+/, '');
                const fileUrl = baseUrl.replace(/\/$/, '') + '/' + normalizedPath;
                const fileLink = document.createElement('a');
                fileLink.href = fileUrl;
                fileLink.target = '_blank';
                fileLink.className = 'd-block mt-2';

                const fileExt = normalizedPath.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                    const img = document.createElement('img');
                    img.src = fileUrl;
                    img.className = 'chat-image';
                    fileLink.appendChild(img);
                } else {
                    fileLink.innerHTML = '<i class="fas fa-file"></i> ' + (message.message ? 'Attachment' : 'File');
                }
                body.appendChild(fileLink);
            }
        }

        const meta = document.createElement('div');
        meta.className = 'small text-muted mt-2';
        meta.textContent = formatTimestamp(message.created_at);
        if (message.updated_at && message.updated_at !== message.created_at) {
            const editedBadge = document.createElement('span');
            editedBadge.className = 'text-muted small ms-2';
            editedBadge.textContent = '(edited)';
            meta.appendChild(editedBadge);
        }

        // Add options menu
        const optionsMenu = document.createElement('div');
        optionsMenu.className = 'dropdown mt-1';
        optionsMenu.innerHTML = `
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="replyToMessage(${message.message_id})"><i class="fas fa-reply"></i> Reply</a></li>
                ${mine ? `<li><a class="dropdown-item" href="#" onclick="editMessage(${message.message_id})"><i class="fas fa-edit"></i> Edit</a></li>` : ''}
                ${mine ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteMessage(${message.message_id})"><i class="fas fa-trash"></i> Delete</a></li>` : ''}
            </ul>
        `;
        meta.appendChild(optionsMenu);

        bubble.append(author, body, meta);
        wrapper.appendChild(bubble);
        return wrapper;
    }

    function capitalizeRole(role) {
        if (!role) {
            return 'Sender';
        }
        return role.charAt(0).toUpperCase() + role.slice(1);
    }

    function formatTimestamp(timestamp) {
        if (!timestamp) {
            return '';
        }
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) {
            return timestamp;
        }
        return date.toLocaleString();
    }

    function appendMessages(messages) {
        if (!Array.isArray(messages) || messages.length === 0) {
            return;
        }

        messages.forEach((message) => {
            messageCache[message.message_id] = message;
            const existing = chatBox.querySelector(`[data-message-id="${message.message_id}"]`);
            const messageElement = createMessageElement(message);
            if (!messageElement) {
                return;
            }
            if (existing) {
                existing.replaceWith(messageElement);
                return;
            }
            chatBox.appendChild(messageElement);
        });

        scrollChatToBottom();
    }

    function scrollChatToBottom() {
        if (!chatBox) return;
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    async function fetchMessages(afterId = 0) {
        clearStatus();
        const params = new URLSearchParams({ ticket_id: ticketId });
        if (afterId > 0) {
            params.append('after_id', afterId);
        }

        try {
            const response = await fetch(`${getMessagesUrl}?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                setStatus('Unable to load messages.', true);
                return;
            }

            const data = await response.json();
            if (data.error) {
                setStatus(data.error, true);
                return;
            }

            appendMessages(data.messages || []);
        } catch (error) {
            setStatus('Unable to refresh messages. Please try again.', true);
        }
    }

    async function sendMessage(event) {
        if (event) {
            event.preventDefault();
        }

        clearStatus();

        const textarea = form.querySelector('textarea[name="message"]');
        const fileInput = form.querySelector('input[name="file"]');
        if (!textarea) {
            return;
        }

        const messageText = textarea.value.trim();
        const hasFile = fileInput && fileInput.files.length > 0;
        if (!messageText && !hasFile) {
            setStatus('Please enter a message or attach a file.', true);
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('message', messageText);
        if (replyInput) {
            formData.append('reply_to', replyInput.value || '');
        }
        if (hasFile) {
            formData.append('file', fileInput.files[0]);
        }

        try {
            const response = await fetch(sendMessageUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            if (!response.ok) {
                const text = await response.text();
                let errorMessage = `Failed to send message (${response.status})`;
                try {
                    const json = JSON.parse(text);
                    if (json.error) {
                        errorMessage = json.error;
                    }
                } catch (parseError) {
                    if (text.trim()) {
                        errorMessage = text.trim();
                    }
                }
                console.error('Send message failed', response.status, text);
                setStatus(errorMessage, true);
                return;
            }

            const data = await response.json();
            if (data.error) {
                setStatus(data.error, true);
                return;
            }

            if (data.success && data.message) {
                appendMessages([data.message]);
                if (form.reset) {
                    form.reset();
                } else {
                    textarea.value = '';
                    if (fileInput) {
                        fileInput.value = '';
                    }
                }
                if (replyInput) {
                    replyInput.value = '';
                }
                if (replyPreviewContainer) {
                    replyPreviewContainer.classList.add('d-none');
                    replyPreviewContainer.textContent = '';
                }
                clearStatus();
            }
        } catch (error) {
            console.error('Send message network/error', error);
            setStatus('Unable to send message. Please try again.', true);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    form.addEventListener('submit', sendMessage);
    if (ticketId > 0) {
        fetchMessages(0);
        setInterval(() => fetchMessages(getLastMessageId()), 5000);
    }

    // Global functions for message actions
    function clearReplyPreview() {
        if (replyInput) {
            replyInput.value = '';
        }
        if (replyPreviewContainer) {
            replyPreviewContainer.classList.add('d-none');
            replyPreviewContainer.textContent = '';
        }
    }

    window.replyToMessage = function(messageId) {
        const textarea = form.querySelector('textarea[name="message"]');
        const message = messageCache[messageId];
        if (!replyInput || !replyPreviewContainer || !message) {
            if (textarea) textarea.focus();
            return;
        }

        replyInput.value = messageId;
        replyPreviewContainer.textContent = 'Replying to: ' + (message.message || (message.file_path ? 'Attachment' : 'Message'));
        replyPreviewContainer.classList.remove('d-none');

        if (textarea) {
            textarea.focus();
        }
    };

    window.editMessage = async function(messageId) {
        const messageElement = chatBox.querySelector(`[data-message-id="${messageId}"]`);
        if (!messageElement) return;

        const bubble = messageElement.querySelector('.rounded');
        if (!bubble) return;

        // The body is the second div inside the bubble (author, body, meta)
        const bodyDiv = bubble.children[1];
        if (!bodyDiv) return;

        const currentText = bodyDiv.textContent.trim();
        const newText = prompt('Edit message:', currentText);
        if (newText === null || newText.trim() === currentText) return;

        const formData = new FormData();
        formData.append('message_id', messageId);
        formData.append('message', newText.trim());

        try {
            const response = await fetch(editMessageUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            if (!response.ok) {
                setStatus('Failed to edit message.', true);
                return;
            }

            const data = await response.json();
            if (data.error) {
                setStatus(data.error, true);
                return;
            }

            // Refresh all messages to show changes
            fetchMessages(0);
            clearStatus();
        } catch (error) {
            setStatus('Unable to edit message. Please try again.', true);
        }
    };

    window.deleteMessage = async function(messageId) {
        if (!confirm('Are you sure you want to delete this message?')) return;

        const formData = new FormData();
        formData.append('message_id', messageId);

        try {
            const response = await fetch(deleteMessageUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            if (!response.ok) {
                setStatus('Failed to delete message.', true);
                return;
            }

            const data = await response.json();
            if (data.error) {
                setStatus(data.error, true);
                return;
            }

            // Refresh all messages to show changes
            fetchMessages(0);
            clearStatus();
        } catch (error) {
            setStatus('Unable to delete message. Please try again.', true);
        }
    };
})();
