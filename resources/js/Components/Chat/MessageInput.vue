<!-- resources/js/components/Chat/MessageInput.vue -->
<template>
    <div class="flex items-center space-x-2">
        <!-- File Upload -->
        <button @click="triggerFileUpload" 
                class="p-2 text-gray-500 hover:text-gray-700">
            📎
        </button>
        <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden">
        
        <!-- Message Input -->
        <input type="text" 
               v-model="message" 
               @keyup.enter="send"
               placeholder="Type a message..."
               class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:border-blue-500">
        
        <!-- Send Button -->
        <button @click="send" 
                :disabled="!message.trim()"
                class="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 disabled:bg-gray-300">
            📤
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const message = ref('');
const fileInput = ref(null);

const emit = defineEmits(['send-message', 'send-file']);

const send = () => {
    if (message.value.trim()) {
        emit('send-message', message.value.trim());
        message.value = '';
    }
};

const triggerFileUpload = () => {
    fileInput.value.click();
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        emit('send-file', file);
        event.target.value = '';
    }
};
</script>