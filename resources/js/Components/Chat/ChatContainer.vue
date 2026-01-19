<!-- resources/js/components/Chat/ChatContainer.vue -->
<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="w-1/4 bg-white border-r border-gray-200">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Messages</h2>
                <button @click="createNewConversation" 
                        class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    New Chat
                </button>
            </div>
            <ConversationList 
                :conversations="conversations" 
                :active-conversation="activeConversation"
                @select-conversation="selectConversation"
            />
        </div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col">
            <!-- Chat Header -->
            <div class="p-4 border-b border-gray-200 bg-white" v-if="activeConversation">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                        {{ activeConversation.name ? activeConversation.name.charAt(0) : 'G' }}
                    </div>
                    <div class="ml-3">
                        <h3 class="font-semibold">
                            {{ activeConversation.name || 'Group Chat' }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ activeConversation.participants_count }} participants
                        </p>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 bg-gray-50" v-if="activeConversation">
                <MessageList 
                    :messages="messages" 
                    :current-user-id="currentUser.id"
                />
            </div>

            <!-- Message Input -->
            <div class="p-4 border-t border-gray-200 bg-white" v-if="activeConversation">
                <MessageInput 
                    @send-message="sendMessage"
                    @send-file="sendFile"
                />
            </div>

            <!-- Empty State -->
            <div v-else class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <div class="text-gray-400 text-6xl mb-4">💬</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Select a conversation</h3>
                    <p class="text-gray-500">Choose a chat from the sidebar or start a new one</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import ConversationList from './ConversationList.vue';
import MessageList from './MessageList.vue';
import MessageInput from './MessageInput.vue';

const props = defineProps({
    currentUser: {
        type: Object,
        required: true
    }
});

const conversations = ref([]);
const messages = ref([]);
const activeConversation = ref(null);
const echo = window.Echo;

const fetchConversations = async () => {
    try {
        const response = await axios.get('/api/conversations');
        conversations.value = response.data;
    } catch (error) {
        console.error('Error fetching conversations:', error);
    }
};

const selectConversation = async (conversation) => {
    activeConversation.value = conversation;
    await fetchMessages(conversation.id);
    listenForMessages();
};

const fetchMessages = async (conversationId) => {
    try {
        const response = await axios.get(`/api/conversations/${conversationId}/messages`);
        messages.value = response.data;
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
};

const sendMessage = async (message) => {
    try {
        const response = await axios.post(`/api/conversations/${activeConversation.value.id}/messages`, {
            body: message
        });
        
        // Add message locally
        messages.value.push({
            ...response.data,
            user: props.currentUser
        });
    } catch (error) {
        console.error('Error sending message:', error);
    }
};

const sendFile = async (file) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', file.type.startsWith('image/') ? 'image' : 'file');
    
    try {
        await axios.post(`/api/conversations/${activeConversation.value.id}/messages`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    } catch (error) {
        console.error('Error sending file:', error);
    }
};

const listenForMessages = () => {
    if (activeConversation.value) {
        echo.private(`chat.${activeConversation.value.id}`)
            .listen('MessageSent', (e) => {
                messages.value.push(e);
            });
    }
};

const createNewConversation = async () => {
    const { value: userName } = await Swal.fire({
        title: 'New Conversation',
        input: 'text',
        inputLabel: 'Enter username to chat with',
        inputPlaceholder: 'Enter username',
        showCancelButton: true,
    });

    if (userName) {
        try {
            const response = await axios.post('/api/conversations', {
                username: userName
            });
            conversations.value.push(response.data);
            selectConversation(response.data);
        } catch (error) {
            Swal.fire('Error', error.response?.data?.message || 'User not found', 'error');
        }
    }
};

onMounted(() => {
    fetchConversations();
});

onUnmounted(() => {
    if (activeConversation.value) {
        echo.leave(`chat.${activeConversation.value.id}`);
    }
});
</script>