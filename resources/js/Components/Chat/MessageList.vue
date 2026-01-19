<!-- resources/js/components/Chat/MessageList.vue -->
<template>
    <div class="space-y-4">
        <div v-for="message in messages" :key="message.id" 
             :class="['flex', message.user.id === currentUserId ? 'justify-end' : 'justify-start']">
            <div :class="['max-w-xs lg:max-w-md rounded-lg p-3', 
                         message.user.id === currentUserId ? 'bg-blue-500 text-white' : 'bg-white border border-gray-200']">
                <!-- Message Header -->
                <div class="flex items-center mb-1">
                    <span class="text-sm font-semibold" v-if="message.user.id !== currentUserId">
                        {{ message.user.name }}
                    </span>
                    <span class="text-xs ml-2 opacity-75">{{ message.time }}</span>
                </div>
                
                <!-- Message Body -->
                <div v-if="message.type === 'text'">
                    {{ message.body }}
                </div>
                
                <!-- Image Message -->
                <div v-else-if="message.type === 'image'">
                    <img :src="`/storage/${message.attachment?.path}`" 
                         alt="Image" 
                         class="max-w-full h-auto rounded">
                </div>
                
                <!-- File Message -->
                <div v-else-if="message.type === 'file'">
                    <a :href="`/storage/${message.attachment?.path}`" 
                       target="_blank"
                       class="flex items-center hover:underline">
                        📎 {{ message.attachment?.filename }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    messages: {
        type: Array,
        default: () => []
    },
    currentUserId: {
        type: Number,
        required: true
    }
});
</script>